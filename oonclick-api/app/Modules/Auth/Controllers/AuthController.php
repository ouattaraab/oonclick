<?php

namespace App\Modules\Auth\Controllers;

use App\Models\DeviceFingerprint;
use App\Models\SubscriberProfile;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Modules\Auth\Requests\CompleteProfileRequest;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Requests\VerifyOtpRequest;
use App\Modules\Auth\Services\OtpService;
use App\Modules\Auth\Services\SmsService;
use App\Models\OtpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly SmsService $smsService,
    ) {}

    /**
     * Enregistre un nouvel utilisateur et envoie un OTP de vérification.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $phone = $request->input('phone');
        $role  = $request->input('role');

        // 1. Vérifier si le compte est déjà actif (téléphone vérifié)
        $existingVerified = User::where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->first();

        if ($existingVerified) {
            return response()->json([
                'message' => 'Compte existant. Veuillez vous connecter.',
            ], 409);
        }

        // 2. Réutiliser un compte non vérifié ou en créer un nouveau
        $user = User::where('phone', $phone)->whereNull('phone_verified_at')->first();

        if (! $user) {
            $user = User::create([
                'phone' => $phone,
                'role'  => $role,
                'name'  => $role === 'advertiser' ? $request->input('name') : null,
            ]);
        }

        // Stocker le code de parrainage dans la session (sera utilisé lors de completeProfile)
        if ($request->filled('referral_code')) {
            // On le stocke sur l'user temporairement via metadata non exposée
            // Le referral_code soumis à l'inscription est traité dans completeProfile
            // On le mémorise dans un cache ou on le passe via le token — ici on le
            // stocke dans le profil en attente pour le retrouver dans completeProfile
            SubscriberProfile::updateOrCreate(
                ['user_id' => $user->id],
                [] // le referral_code sera validé dans completeProfile
            );
            // On utilise la session/cache pour transmettre le referral_code
            cache()->put("register_referral_{$user->id}", $request->input('referral_code'), now()->addHour());
        }

        // 3. Générer et envoyer l'OTP
        $code = $this->otpService->generate($phone, 'registration');
        $this->smsService->sendOtp($phone, $code);

        return response()->json([
            'message' => 'Code envoyé.',
            'phone'   => $phone,
        ], 201);
    }

    /**
     * Vérifie le code OTP soumis et active le compte (ou connecte l'utilisateur).
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->input('phone');
        $code  = $request->input('code');
        $type  = $request->input('type');

        // 1. Vérifier le code OTP
        if (! $this->otpService->verify($phone, $code, $type)) {
            return response()->json([
                'message' => 'Code invalide ou expiré.',
            ], 422);
        }

        // 2. Retrouver l'utilisateur
        $user = User::where('phone', $phone)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        // 3. Activer le compte + opérations wallet dans une transaction DB
        $token = DB::transaction(function () use ($user, $request, $type) {
            // Marquer le téléphone comme vérifié et activer le compte
            $user->phone_verified_at = now();
            $user->is_active         = true;
            $user->save();

            // 4. Créer le wallet si inexistant
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance'          => 0,
                    'pending_balance'  => 0,
                    'total_earned'     => 0,
                    'total_withdrawn'  => 0,
                ]
            );

            // 5. Bonus d'inscription 500 FCFA pour les nouveaux subscribers (registration uniquement)
            if ($type === 'registration' && $user->isSubscriber()) {
                $bonus     = config('oonclick.signup_bonus', 500);
                $reference = "SIGNUP_BONUS_{$user->id}";

                // Éviter le double crédit (idempotence)
                $alreadyBonused = WalletTransaction::where('reference', $reference)->exists();

                if (! $alreadyBonused) {
                    $newBalance = $wallet->balance + $bonus;

                    WalletTransaction::create([
                        'wallet_id'     => $wallet->id,
                        'type'          => 'bonus',
                        'amount'        => $bonus,
                        'balance_after' => $newBalance,
                        'reference'     => $reference,
                        'description'   => 'Bonus inscription oon.click',
                        'status'        => 'completed',
                    ]);

                    $wallet->balance      = $newBalance;
                    $wallet->total_earned = $wallet->total_earned + $bonus;
                    $wallet->save();
                }
            }

            // 6. Enregistrer le device fingerprint si fourni
            if ($request->filled('device_fingerprint')) {
                DeviceFingerprint::updateOrCreate(
                    [
                        'user_id'          => $user->id,
                        'fingerprint_hash' => $request->input('device_fingerprint'),
                    ],
                    [
                        'platform'     => $request->input('platform'),
                        'device_model' => $request->input('device_model'),
                        'is_trusted'   => true,
                        'last_seen_at' => now(),
                    ]
                );
            }

            // 7. Générer le token Sanctum
            return $user->createToken('mobile')->plainTextToken;
        });

        // Recharger le wallet pour retourner le solde à jour
        $user->load('wallet');

        // Envoyer la notification de bienvenue uniquement lors de l'inscription
        if ($type === 'registration') {
            $user->notify(new \App\Notifications\WelcomeNotification($user));
        }

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'          => $user->id,
                'phone'       => $user->phone,
                'role'        => $user->role,
                'kyc_level'   => $user->kyc_level,
                'trust_score' => $user->trust_score,
            ],
            'wallet' => [
                'balance' => $user->wallet?->balance ?? 0,
            ],
        ], 200);
    }

    /**
     * Renvoie un nouveau code OTP (limité à 3 envois par 10 minutes).
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'type'  => ['required', 'in:registration,login'],
        ]);

        $phone = $request->input('phone');
        $type  = $request->input('type');

        // Vérifier que l'utilisateur existe
        $user = User::where('phone', $phone)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        // Throttle : max 3 envois dans les 10 dernières minutes
        $recentCount = OtpCode::where('phone', $phone)
            ->where('type', $type)
            ->where('created_at', '>', now()->subMinutes(10))
            ->count();

        if ($recentCount >= 3) {
            return response()->json([
                'message' => 'Trop de tentatives. Veuillez patienter avant de demander un nouveau code.',
            ], 429);
        }

        $code = $this->otpService->generate($phone, $type);
        $this->smsService->sendOtp($phone, $code);

        return response()->json([
            'message' => 'Code renvoyé.',
        ], 200);
    }

    /**
     * Initie une connexion par OTP pour un compte existant et actif.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $phone = $request->input('phone');

        // 1. Vérifier que le compte existe
        $user = User::where('phone', $phone)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Aucun compte trouvé pour ce numéro.',
            ], 404);
        }

        // 2. Vérifier que le compte n'est pas suspendu
        if ($user->is_suspended) {
            return response()->json([
                'message' => 'Votre compte est suspendu. Contactez le support.',
            ], 403);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Votre compte est inactif.',
            ], 403);
        }

        // 3. Vérifier que le téléphone a été vérifié
        if (! $user->isPhoneVerified()) {
            return response()->json([
                'message' => 'Compte non vérifié. Veuillez d\'abord vérifier votre numéro.',
            ], 403);
        }

        // 4. Générer et envoyer l'OTP de connexion
        $code = $this->otpService->generate($phone, 'login');
        $this->smsService->sendOtp($phone, $code);

        return response()->json([
            'message' => 'Code envoyé.',
        ], 200);
    }

    /**
     * Révoque le token Sanctum courant.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnecté.',
        ], 200);
    }

    /**
     * Retourne le profil complet de l'utilisateur authentifié.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('profile', 'wallet');

        return response()->json([
            'user' => [
                'id'           => $user->id,
                'name'         => $user->name,
                'phone'        => $user->phone,
                'email'        => $user->email,
                'role'         => $user->role,
                'kyc_level'    => $user->kyc_level,
                'trust_score'  => $user->trust_score,
                'is_active'    => $user->is_active,
                'is_suspended' => $user->is_suspended,
            ],
            'profile' => $user->profile,
            'wallet'  => $user->wallet ? [
                'balance'         => $user->wallet->balance,
                'pending_balance' => $user->wallet->pending_balance,
                'total_earned'    => $user->wallet->total_earned,
                'total_withdrawn' => $user->wallet->total_withdrawn,
            ] : null,
        ], 200);
    }

    /**
     * Complète le profil d'un subscriber après inscription.
     */
    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isSubscriber()) {
            return response()->json([
                'message' => 'Cette action est réservée aux subscribers.',
            ], 403);
        }

        $profile = DB::transaction(function () use ($user, $request) {
            // 1. Créer ou mettre à jour le profil
            $profile = SubscriberProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name'  => $request->input('first_name'),
                    'last_name'   => $request->input('last_name'),
                    'gender'      => $request->input('gender'),
                    'date_of_birth' => $request->input('date_of_birth'),
                    'city'        => $request->input('city'),
                    'operator'    => $request->input('operator'),
                    'interests'   => $request->input('interests', []),
                ]
            );

            // 2. Générer un referral_code unique si pas encore défini
            if (! $profile->referral_code) {
                do {
                    $referralCode = strtoupper(Str::random(8));
                } while (SubscriberProfile::where('referral_code', $referralCode)->exists());

                $profile->referral_code = $referralCode;
            }

            // 3. Traiter le parrainage si un code avait été soumis à l'inscription
            $pendingReferral = cache()->pull("register_referral_{$user->id}");

            if ($pendingReferral && ! $profile->referred_by) {
                $referrerProfile = SubscriberProfile::where('referral_code', $pendingReferral)->first();

                if ($referrerProfile) {
                    $profile->referred_by = $referrerProfile->user_id;

                    $referralBonus = config('oonclick.referral_bonus', 200);

                    // Bonus pour le filleul (cet utilisateur)
                    $userWallet = Wallet::firstOrCreate(['user_id' => $user->id]);
                    $userNewBalance = $userWallet->balance + $referralBonus;

                    WalletTransaction::create([
                        'wallet_id'     => $userWallet->id,
                        'type'          => 'bonus',
                        'amount'        => $referralBonus,
                        'balance_after' => $userNewBalance,
                        'reference'     => "REFERRAL_FILLEUL_{$user->id}",
                        'description'   => 'Bonus parrainage — filleul',
                        'status'        => 'completed',
                    ]);

                    $userWallet->balance      = $userNewBalance;
                    $userWallet->total_earned = $userWallet->total_earned + $referralBonus;
                    $userWallet->save();

                    // Bonus pour le référent
                    $referrerWallet = Wallet::firstOrCreate(['user_id' => $referrerProfile->user_id]);
                    $referrerNewBalance = $referrerWallet->balance + $referralBonus;

                    WalletTransaction::create([
                        'wallet_id'     => $referrerWallet->id,
                        'type'          => 'bonus',
                        'amount'        => $referralBonus,
                        'balance_after' => $referrerNewBalance,
                        'reference'     => "REFERRAL_PARRAIN_{$referrerProfile->user_id}_FOR_{$user->id}",
                        'description'   => "Bonus parrainage — filleul #{$user->id}",
                        'status'        => 'completed',
                    ]);

                    $referrerWallet->balance      = $referrerNewBalance;
                    $referrerWallet->total_earned = $referrerWallet->total_earned + $referralBonus;
                    $referrerWallet->save();
                }
            }

            // 4. Marquer le profil comme complété
            $profile->profile_completed_at = now();
            $profile->save();

            return $profile;
        });

        return response()->json([
            'message' => 'Profil complété.',
            'profile' => $profile,
        ], 200);
    }
}
