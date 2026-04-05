<?php

namespace App\Modules\Auth\Controllers;

use App\Models\DeviceFingerprint;
use App\Models\FeatureSetting;
use App\Models\ReferralEarning;
use App\Models\SubscriberProfile;
use App\Models\User;
use App\Models\UserConsent;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Modules\Auth\Requests\CompleteProfileRequest;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Requests\VerifyOtpRequest;
use App\Modules\Auth\Services\OtpService;
use App\Modules\Auth\Services\SmsService;
use App\Models\OtpCode;
use App\Services\GamificationService;
use App\Services\MissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly SmsService $smsService,
        private readonly GamificationService $gamificationService,
    ) {}

    /**
     * Enregistre un nouvel utilisateur et envoie un OTP de vérification.
     *
     * Supporte deux méthodes :
     * - phone : crée le user, l'OTP SMS sera envoyé par Firebase côté mobile
     * - email : crée le user et envoie l'OTP par e-mail
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $method = $request->input('method', $request->has('email') ? 'email' : 'phone');
        $role   = $request->input('role');

        if ($method === 'email') {
            return $this->registerByEmail($request, $role);
        }

        return $this->registerByPhone($request, $role);
    }

    private function registerByPhone(RegisterRequest $request, string $role): JsonResponse
    {
        $phone = $request->input('phone');

        // Vérifier si le compte est déjà actif
        $existingVerified = User::where('phone', $phone)
            ->whereNotNull('phone_verified_at')
            ->first();

        if ($existingVerified) {
            return response()->json([
                'message' => 'Compte existant. Veuillez vous connecter.',
            ], 409);
        }

        // Réutiliser un compte non vérifié ou en créer un nouveau
        $user = User::where('phone', $phone)->whereNull('phone_verified_at')->first();

        if (! $user) {
            $user = User::create([
                'phone' => $phone,
                'role'  => $role,
                'name'  => $role === 'advertiser' ? $request->input('name') : null,
            ]);
        }

        $this->storeReferralCode($request, $user);
        $this->recordConsentsFromRequest($request, $user->id);

        // Générer l'OTP et l'envoyer par SMS.
        // Firebase Phone Auth peut prendre le relais côté mobile, mais
        // on envoie aussi par SMS comme fallback.
        $code = $this->otpService->generate($phone, 'registration');
        $this->smsService->sendOtp($phone, $code);

        return response()->json([
            'message' => 'Inscription initiée. Vérifiez votre téléphone.',
            'phone'   => $phone,
            'method'  => 'phone',
        ], 201);
    }

    private function registerByEmail(RegisterRequest $request, string $role): JsonResponse
    {
        $email = strtolower(trim($request->input('email')));

        // Vérifier si le compte est déjà actif
        $existingVerified = User::where('email', $email)
            ->whereNotNull('email_verified_at')
            ->first();

        if ($existingVerified) {
            return response()->json([
                'message' => 'Compte existant. Veuillez vous connecter.',
            ], 409);
        }

        // Réutiliser un compte non vérifié ou en créer un nouveau
        $user = User::where('email', $email)->whereNull('email_verified_at')->first();

        if (! $user) {
            $user = User::create([
                'email' => $email,
                'role'  => $role,
                'name'  => $role === 'advertiser' ? $request->input('name') : null,
            ]);
        }

        $this->storeReferralCode($request, $user);
        $this->recordConsentsFromRequest($request, $user->id);

        // Générer et envoyer l'OTP par e-mail
        $code = $this->otpService->generate($email, 'registration');
        $this->sendOtpByEmail($email, $code);

        return response()->json([
            'message' => 'Code envoyé par e-mail.',
            'email'   => $email,
            'method'  => 'email',
        ], 201);
    }

    /**
     * Records the 6 granular consents supplied in the registration request.
     *
     * Maps the mobile field names (consent_cgu, consent_targeting, …) to the
     * server consent type codes (C1–C6).  Only records consents that were
     * explicitly submitted; missing fields default to `false`.
     */
    private function recordConsentsFromRequest(RegisterRequest $request, int $userId): void
    {
        $ip = $request->ip();
        $ua = $request->userAgent();

        $map = [
            'C1' => (bool) $request->input('consent_cgu', false),
            'C2' => (bool) $request->input('consent_targeting', false),
            'C3' => (bool) $request->input('consent_transfer', false),
            'C4' => (bool) $request->input('consent_fingerprint', false),
            'C5' => (bool) $request->input('consent_notifications', false),
            'C6' => (bool) $request->input('consent_marketing', false),
        ];

        // Only persist if at least one consent flag was sent (avoids recording
        // empty consents for legacy clients that don't send these fields yet).
        $hasAny = array_filter(array_keys($map), fn ($k) => $request->has(strtolower("consent_{$k}")));

        if (empty($hasAny) && ! $request->hasAny([
            'consent_cgu', 'consent_targeting', 'consent_transfer',
            'consent_fingerprint', 'consent_notifications', 'consent_marketing',
        ])) {
            return;
        }

        foreach ($map as $type => $granted) {
            UserConsent::record($userId, $type, $granted, $ip, $ua);
        }
    }

    private function storeReferralCode(RegisterRequest $request, User $user): void
    {
        if ($request->filled('referral_code')) {
            SubscriberProfile::updateOrCreate(
                ['user_id' => $user->id],
                []
            );
            cache()->put("register_referral_{$user->id}", $request->input('referral_code'), now()->addHour());
        }
    }

    /**
     * Envoie un OTP par e-mail.
     */
    private function sendOtpByEmail(string $email, string $code): void
    {
        try {
            Mail::raw(
                "Votre code de vérification oon.click est : $code\n\nCe code expire dans 10 minutes.\n\nSi vous n'avez pas demandé ce code, ignorez ce message.",
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('oon.click — Code de vérification');
                }
            );
        } catch (\Throwable $e) {
            Log::error("Failed to send OTP email to {$email}: {$e->getMessage()}");
        }
    }

    /**
     * Vérifie le code OTP soumis (email ou phone backend).
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->input('phone');
        $code  = $request->input('code');
        $type  = $request->input('type');

        // Déterminer l'identifiant (phone ou email)
        $identifier = $phone;

        // 1. Vérifier le code OTP
        if (! $this->otpService->verify($identifier, $code, $type)) {
            return response()->json([
                'message' => 'Code invalide ou expiré.',
            ], 422);
        }

        // 2. Retrouver l'utilisateur (par phone ou email)
        $user = User::where('phone', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        // 3. Activer le compte
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

        $token = DB::transaction(function () use ($user, $request, $type, $isEmail) {
            if ($isEmail) {
                $user->email_verified_at = now();
            } else {
                $user->phone_verified_at = now();
            }
            $user->is_active = true;
            $user->save();

            // Créer le wallet si inexistant
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance'          => 0,
                    'pending_balance'  => 0,
                    'total_earned'     => 0,
                    'total_withdrawn'  => 0,
                ]
            );

            // Bonus d'inscription 500 FCFA pour les nouveaux subscribers
            if ($type === 'registration' && $user->isSubscriber()) {
                $this->awardSignupBonus($user, $wallet);
            }

            // Enregistrer le device fingerprint si fourni
            $this->registerDeviceFingerprint($user, $request);

            return $user->createToken('mobile')->plainTextToken;
        });

        $user->load('wallet');

        if ($type === 'registration') {
            try {
                $user->notify(new \App\Notifications\WelcomeNotification($user));
            } catch (\Throwable $e) {
                Log::warning("Welcome notification failed: {$e->getMessage()}");
            }
        }

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'          => $user->id,
                'phone'       => $user->phone,
                'email'       => $user->email,
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
     * Authentification via Google (Firebase ID token).
     *
     * POST /api/auth/google
     *
     * Crée le user si inexistant, ou connecte s'il existe déjà.
     */
    public function googleAuth(Request $request): JsonResponse
    {
        $request->validate([
            'firebase_id_token' => ['required', 'string'],
            'email'             => ['required', 'email'],
            'name'              => ['nullable', 'string', 'max:150'],
            'role'              => ['sometimes', 'in:subscriber,advertiser'],
            'fingerprint'       => ['nullable', 'string', 'max:64'],
            'platform'          => ['nullable', 'in:android,ios,web'],
        ]);

        $idToken = $request->input('firebase_id_token');
        $email   = strtolower(trim($request->input('email')));
        $name    = $request->input('name');
        $role    = $request->input('role', 'subscriber');

        // Vérifier le token Firebase
        $firebaseUser = $this->verifyFirebaseIdToken($idToken);

        if (! $firebaseUser) {
            return response()->json([
                'message' => 'Token Firebase invalide ou expiré.',
            ], 401);
        }

        // Vérifier que l'email dans le token Firebase correspond
        $firebaseEmail = $firebaseUser['email'] ?? null;
        if (! $firebaseEmail || strtolower($firebaseEmail) !== $email) {
            return response()->json([
                'message' => 'L\'email vérifié par Google ne correspond pas.',
            ], 422);
        }

        // Chercher ou créer l'utilisateur
        $user = User::where('email', $email)->first();
        $isNewUser = false;

        if (! $user) {
            $isNewUser = true;
            $user = User::create([
                'email' => $email,
                'name'  => $name,
                'role'  => $role,
            ]);
        }

        // Vérifications pour un utilisateur existant
        if (! $isNewUser) {
            if ($user->is_suspended) {
                return response()->json([
                    'message' => 'Votre compte est suspendu. Contactez le support.',
                ], 403);
            }
        }

        // Activer et authentifier
        $token = DB::transaction(function () use ($user, $request, $isNewUser, $name) {
            $user->email_verified_at = now();
            $user->is_active = true;

            // Mettre à jour le nom si pas déjà défini et fourni par Google
            if (! $user->name && $name) {
                $user->name = $name;
            }

            $user->save();

            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance'          => 0,
                    'pending_balance'  => 0,
                    'total_earned'     => 0,
                    'total_withdrawn'  => 0,
                ]
            );

            // Bonus d'inscription pour les nouveaux subscribers
            if ($isNewUser && $user->isSubscriber()) {
                $this->awardSignupBonus($user, $wallet);
            }

            $this->registerDeviceFingerprint($user, $request);

            return $user->createToken('mobile')->plainTextToken;
        });

        $user->load('wallet');

        if ($isNewUser) {
            try {
                $user->notify(new \App\Notifications\WelcomeNotification($user));
            } catch (\Throwable $e) {
                Log::warning("Welcome notification failed: {$e->getMessage()}");
            }
        }

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'          => $user->id,
                'name'        => $user->name,
                'phone'       => $user->phone,
                'email'       => $user->email,
                'role'        => $user->role,
                'kyc_level'   => $user->kyc_level,
                'trust_score' => $user->trust_score,
            ],
            'wallet' => [
                'balance' => $user->wallet?->balance ?? 0,
            ],
            'is_new_user' => $isNewUser,
        ], 200);
    }

    /**
     * Vérifie un token Firebase ID et authentifie l'utilisateur.
     *
     * POST /api/auth/verify-firebase
     *
     * Utilisé après que Firebase Phone Auth ait vérifié le numéro côté mobile.
     */
    public function verifyFirebase(Request $request): JsonResponse
    {
        $request->validate([
            'phone'             => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'firebase_id_token' => ['required', 'string'],
            'type'              => ['required', 'in:registration,login'],
            'fingerprint'       => ['nullable', 'string', 'max:64'],
            'platform'          => ['nullable', 'in:android,ios,web'],
        ]);

        $phone      = $request->input('phone');
        $idToken    = $request->input('firebase_id_token');
        $type       = $request->input('type');

        // Vérifier le token Firebase via l'API Google
        $firebaseUser = $this->verifyFirebaseIdToken($idToken);

        if (! $firebaseUser) {
            return response()->json([
                'message' => 'Token Firebase invalide ou expiré.',
            ], 401);
        }

        // Vérifier que le numéro dans le token correspond
        $firebasePhone = $firebaseUser['phone_number'] ?? null;
        if (! $firebasePhone || ! $this->phonesMatch($phone, $firebasePhone)) {
            return response()->json([
                'message' => 'Le numéro vérifié par Firebase ne correspond pas.',
            ], 422);
        }

        // Retrouver l'utilisateur
        $user = User::where('phone', $phone)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Utilisateur introuvable. Veuillez d\'abord vous inscrire.',
            ], 404);
        }

        // Vérifications pour le login
        if ($type === 'login') {
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
        }

        // Activer le compte et générer le token
        $token = DB::transaction(function () use ($user, $request, $type) {
            $user->phone_verified_at = now();
            $user->is_active = true;
            $user->save();

            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance'          => 0,
                    'pending_balance'  => 0,
                    'total_earned'     => 0,
                    'total_withdrawn'  => 0,
                ]
            );

            if ($type === 'registration' && $user->isSubscriber()) {
                $this->awardSignupBonus($user, $wallet);
            }

            $this->registerDeviceFingerprint($user, $request);

            return $user->createToken('mobile')->plainTextToken;
        });

        $user->load('wallet');

        if ($type === 'registration') {
            try {
                $user->notify(new \App\Notifications\WelcomeNotification($user));
            } catch (\Throwable $e) {
                Log::warning("Welcome notification failed: {$e->getMessage()}");
            }
        }

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'          => $user->id,
                'phone'       => $user->phone,
                'email'       => $user->email,
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
     * Vérifie un Firebase ID token via l'API Google.
     *
     * Validation en deux étapes :
     * 1. Pré-validation locale du JWT (structure, audience, expiration) — rejet
     *    immédiat sans appel réseau si le token est manifestement invalide.
     * 2. Vérification auprès de l'API Google accounts:lookup comme source de
     *    vérité finale (signature, révocation, existence du compte).
     *
     * @return array|null Les données de l'utilisateur Firebase, ou null si invalide.
     */
    private function verifyFirebaseIdToken(string $idToken): ?array
    {
        // ------------------------------------------------------------------
        // Étape 1 : pré-validation locale du JWT (sans paquet externe)
        // ------------------------------------------------------------------

        // 1a. Structure : exactement 3 segments séparés par des points
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            Log::warning('Firebase ID token: invalid JWT structure (expected 3 parts).');
            return null;
        }

        // 1b. Décoder le payload (2e segment, base64url sans padding)
        $payloadJson = base64_decode(strtr($parts[1], '-_', '+/'), true);
        if ($payloadJson === false) {
            Log::warning('Firebase ID token: payload base64 decoding failed.');
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (! is_array($payload)) {
            Log::warning('Firebase ID token: payload JSON decoding failed.');
            return null;
        }

        // 1c. Vérifier le claim `aud` (audience = Firebase project ID)
        $expectedProjectId = config('services.firebase.project_id', config('firebase.project_id', ''));
        $aud = $payload['aud'] ?? null;
        // `aud` peut être une chaîne ou un tableau selon la spec JWT
        $audMatches = is_array($aud)
            ? in_array($expectedProjectId, $aud, true)
            : ($aud === $expectedProjectId);

        if ($expectedProjectId !== '' && ! $audMatches) {
            Log::warning('Firebase ID token: audience mismatch.', [
                'expected' => $expectedProjectId,
                'received' => $aud,
            ]);
            return null;
        }

        // 1d. Vérifier le claim `exp` (expiration)
        $exp = $payload['exp'] ?? null;
        if (! is_numeric($exp) || (int) $exp <= time()) {
            Log::warning('Firebase ID token: token expired or missing exp claim.', [
                'exp' => $exp,
                'now' => time(),
            ]);
            return null;
        }

        // ------------------------------------------------------------------
        // Étape 2 : vérification auprès de l'API Google (source de vérité)
        // ------------------------------------------------------------------

        try {
            $url = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key='
                . config('services.firebase.api_key', config('firebase.api_key', ''));

            $response = \Illuminate\Support\Facades\Http::post($url, [
                'idToken' => $idToken,
            ]);

            if ($response->successful()) {
                $users = $response->json('users', []);
                return ! empty($users) ? $users[0] : null;
            }

            Log::warning('Firebase ID token verification failed: ' . $response->body());
            return null;
        } catch (\Throwable $e) {
            Log::error('Firebase ID token verification error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Compare deux numéros de téléphone (gère les formats +225 vs 225 vs brut).
     */
    private function phonesMatch(string $a, string $b): bool
    {
        $normalize = fn(string $phone) => preg_replace('/[^0-9]/', '', $phone);
        $na = $normalize($a);
        $nb = $normalize($b);

        // Comparer les derniers 9-10 chiffres (ignorer le code pays)
        $minLen = min(strlen($na), strlen($nb));
        if ($minLen < 9) return false;

        return substr($na, -9) === substr($nb, -9);
    }

    /**
     * Attribue le bonus d'inscription.
     */
    private function awardSignupBonus(User $user, Wallet $wallet): void
    {
        $bonus     = config('oonclick.signup_bonus', 500);
        $reference = "SIGNUP_BONUS_{$user->id}";

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

    /**
     * Enregistre le device fingerprint si fourni.
     */
    private function registerDeviceFingerprint(User $user, Request $request): void
    {
        if ($request->filled('device_fingerprint') || $request->filled('fingerprint')) {
            $fp = $request->input('device_fingerprint') ?? $request->input('fingerprint');
            DeviceFingerprint::updateOrCreate(
                [
                    'user_id'          => $user->id,
                    'fingerprint_hash' => $fp,
                ],
                [
                    'platform'     => $request->input('platform'),
                    'device_model' => $request->input('device_model'),
                    'is_trusted'   => true,
                    'last_seen_at' => now(),
                ]
            );
        }
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

        $identifier = $request->input('phone');
        $type       = $request->input('type');
        $isEmail    = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

        // Vérifier que l'utilisateur existe
        $user = $isEmail
            ? User::where('email', $identifier)->first()
            : User::where('phone', $identifier)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Utilisateur introuvable.',
            ], 404);
        }

        // Throttle : max 3 envois dans les 10 dernières minutes
        $recentCount = OtpCode::where('phone', $identifier)
            ->where('type', $type)
            ->where('created_at', '>', now()->subMinutes(10))
            ->count();

        if ($recentCount >= 3) {
            return response()->json([
                'message' => 'Trop de tentatives. Veuillez patienter avant de demander un nouveau code.',
            ], 429);
        }

        $code = $this->otpService->generate($identifier, $type);

        if ($isEmail) {
            $this->sendOtpByEmail($identifier, $code);
        } else {
            $this->smsService->sendOtp($identifier, $code);
        }

        return response()->json([
            'message' => 'Code renvoyé.',
        ], 200);
    }

    /**
     * Initie une connexion par OTP pour un compte existant et actif.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $method = $request->input('method', 'phone');

        if ($method === 'email') {
            return $this->loginByEmail($request);
        }

        return $this->loginByPhone($request);
    }

    private function loginByPhone(LoginRequest $request): JsonResponse
    {
        $phone = $request->input('phone');
        $genericMessage = 'Si ce numéro est enregistré, vous recevrez un code.';

        if (! $phone) {
            return response()->json(['message' => $genericMessage], 200);
        }

        $user = User::where('phone', $phone)->first();

        // Generic response to prevent user enumeration
        if (! $user || $user->is_suspended || ! $user->is_active) {
            return response()->json(['message' => $genericMessage], 200);
        }

        $code = $this->otpService->generate($phone, 'login');
        $this->smsService->sendOtp($phone, $code);

        return response()->json([
            'message' => $genericMessage,
            'method'  => 'phone',
        ], 200);
    }

    private function loginByEmail(LoginRequest $request): JsonResponse
    {
        $email = strtolower(trim($request->input('email')));
        $genericMessage = 'Si cette adresse est enregistrée, vous recevrez un code par e-mail.';

        $user = User::where('email', $email)->first();

        // Generic response to prevent user enumeration
        if (! $user || $user->is_suspended || ! $user->is_active) {
            return response()->json(['message' => $genericMessage], 200);
        }

        $code = $this->otpService->generate($email, 'login');
        $this->sendOtpByEmail($email, $code);

        return response()->json([
            'message' => $genericMessage,
            'method'  => 'email',
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
     * Exporte toutes les données de l'utilisateur au format JSON.
     */
    public function exportData(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = SubscriberProfile::where('user_id', $user->id)->first();
        $wallet  = Wallet::where('user_id', $user->id)->first();
        $transactions = $wallet
            ? WalletTransaction::where('wallet_id', $wallet->id)->orderByDesc('created_at')->get()
            : collect();
        $views = \App\Models\AdView::where('subscriber_id', $user->id)->with('campaign:id,title')->get();
        $consents = UserConsent::where('user_id', $user->id)->get();

        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'role'       => $user->role,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'profile' => $profile ? [
                'city'      => $profile->city,
                'gender'    => $profile->gender,
                'operator'  => $profile->operator,
                'interests' => $profile->interests,
            ] : null,
            'wallet' => $wallet ? [
                'balance'      => $wallet->balance,
                'total_earned' => $wallet->total_earned,
            ] : null,
            'transactions' => $transactions->map(fn ($tx) => [
                'type'        => $tx->type,
                'amount'      => $tx->amount,
                'description' => $tx->description,
                'created_at'  => $tx->created_at?->toIso8601String(),
            ])->toArray(),
            'ad_views' => $views->map(fn ($v) => [
                'campaign' => $v->campaign?->title,
                'watched'  => $v->watch_duration_seconds,
                'credited' => $v->amount_credited,
                'date'     => $v->completed_at?->toIso8601String(),
            ])->toArray(),
            'consents' => $consents->map(fn ($c) => [
                'type'    => $c->consent_type,
                'granted' => $c->granted,
                'date'    => $c->created_at?->toIso8601String(),
            ])->toArray(),
        ]);
    }

    /**
     * Supprime définitivement le compte de l'utilisateur.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            // Supprimer les tokens Sanctum
            $user->tokens()->delete();

            // Supprimer le wallet et les transactions
            $wallet = Wallet::where('user_id', $user->id)->first();
            if ($wallet) {
                WalletTransaction::where('wallet_id', $wallet->id)->delete();
                $wallet->delete();
            }

            // Supprimer le profil
            SubscriberProfile::where('user_id', $user->id)->delete();

            // Supprimer les consentements
            UserConsent::where('user_id', $user->id)->delete();

            // Supprimer les vues
            \App\Models\AdView::where('subscriber_id', $user->id)->delete();

            // Supprimer les OTP
            OtpCode::where('phone', $user->phone)
                ->orWhere('phone', $user->email)
                ->delete();

            // Soft delete l'utilisateur
            $user->delete();
        });

        return response()->json([
            'message' => 'Votre compte a été supprimé définitivement.',
        ]);
    }

    /**
     * Retourne le profil complet de l'utilisateur authentifié.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('profile', 'wallet');

        $totalViews = \App\Models\AdView::where('subscriber_id', $user->id)
            ->where('is_completed', true)
            ->count();

        $referralCount = $user->profile
            ? \App\Models\SubscriberProfile::where('referred_by', $user->id)->count()
            : 0;

        // Build avatar URL
        $avatarUrl = null;
        if ($user->avatar_path) {
            $disk = app()->environment('local', 'testing') ? 'public' : 'r2';
            $avatarUrl = $disk === 'public'
                ? url('storage/' . $user->avatar_path)
                : rtrim(config('filesystems.disks.r2.url'), '/') . '/' . $user->avatar_path;
        }

        return response()->json([
            'user' => [
                'id'            => $user->id,
                'name'          => $user->name,
                'phone'         => $user->phone,
                'email'         => $user->email,
                'role'          => $user->role,
                'kyc_level'     => $user->kyc_level,
                'trust_score'   => $user->trust_score,
                'is_active'     => $user->is_active,
                'is_suspended'  => $user->is_suspended,
                'total_views'   => $totalViews,
                'avatar_url'    => $avatarUrl,
            ],
            'profile' => $user->profile
                ? [
                    'city'                 => $user->profile->city,
                    'gender'               => $user->profile->gender,
                    'operator'             => $user->profile->operator,
                    'interests'            => $user->profile->interests,
                    'date_of_birth'        => $user->profile->date_of_birth,
                    'referral_code'        => $user->profile->referral_code,
                    'custom_fields'        => $user->profile->custom_fields,
                    'profile_completed_at' => $user->profile->profile_completed_at,
                    'referral_count'       => $referralCount,
                ]
                : null,
            'wallet'  => $user->wallet ? [
                'balance'         => $user->wallet->balance,
                'pending_balance' => $user->wallet->pending_balance,
                'total_earned'    => $user->wallet->total_earned,
                'total_withdrawn' => $user->wallet->total_withdrawn,
            ] : null,
        ], 200);
    }

    /**
     * Met à jour le profil de l'utilisateur authentifié.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSubscriber()) {
            $validated = $request->validate([
                'first_name'    => ['sometimes', 'string', 'max:100'],
                'last_name'     => ['sometimes', 'string', 'max:100'],
                'gender'        => ['sometimes', 'in:male,female,other'],
                'date_of_birth' => ['sometimes', 'date', 'before:today'],
                'city'          => ['sometimes', 'string', 'max:100'],
                'operator'      => ['sometimes', 'in:mtn,moov,orange,wave'],
                'interests'     => ['sometimes', 'array'],
                'interests.*'   => ['string', 'max:50'],
            ]);

            $profile = SubscriberProfile::firstOrCreate(['user_id' => $user->id]);
            $profile->fill($validated);

            // Persister les custom_fields si fournis (critères d'audience dynamiques)
            if ($request->has('custom_fields')) {
                $validated = $request->validate([
                    'custom_fields'   => ['nullable', 'array', 'max:20'],
                    'custom_fields.*' => ['string', 'max:255'],
                ]);
                $existing = $profile->custom_fields ?? [];
                $new      = $validated['custom_fields'] ?? [];
                $profile->custom_fields = array_merge($existing, $new);
            }

            $profile->save();

            // Synchroniser user.name si first_name ou last_name changent.
            if (isset($validated['first_name']) || isset($validated['last_name'])) {
                $user->name = trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? ''));
                $user->save();
            }

            return response()->json([
                'message' => 'Profil mis à jour.',
                'profile' => $profile->fresh(),
            ], 200);
        }

        if ($user->isAdvertiser()) {
            $validated = $request->validate([
                'name'  => ['sometimes', 'string', 'max:150'],
                'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            ]);

            $user->fill($validated);
            $user->save();

            return response()->json([
                'message' => 'Profil mis à jour.',
                'user'    => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ], 200);
        }

        return response()->json([
            'message' => 'Mise à jour du profil non supportée pour ce rôle.',
        ], 403);
    }

    /**
     * Met à jour l'avatar de l'utilisateur authentifié.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ], [
            'avatar.required' => 'L\'image est requise.',
            'avatar.image'    => 'Le fichier doit être une image.',
            'avatar.max'      => 'L\'image ne doit pas dépasser 2 Mo.',
            'avatar.mimes'    => 'Format accepté : jpg, jpeg, png, webp.',
        ]);

        $user      = $request->user();
        $file      = $request->file('avatar');
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
        $filename  = "avatars/{$user->id}_" . time() . ".{$extension}";

        // Use local storage in dev, R2 in production
        $disk = app()->environment('local', 'testing') ? 'public' : 'r2';

        if ($user->avatar_path && Storage::disk($disk)->exists($user->avatar_path)) {
            Storage::disk($disk)->delete($user->avatar_path);
        }

        Storage::disk($disk)->putFileAs('', $file, $filename);

        $user->avatar_path = $filename;
        $user->save();

        $avatarUrl = $disk === 'public'
            ? url('storage/' . $filename)
            : rtrim(config('filesystems.disks.r2.url'), '/') . '/' . $filename;

        return response()->json([
            'message'    => 'Avatar mis à jour.',
            'avatar_url' => $avatarUrl,
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
            // Mettre à jour le nom du user (utilisé par le mobile pour détecter la complétion du profil)
            $user->name = trim($request->input('first_name') . ' ' . $request->input('last_name'));
            $user->save();

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

            if (! $profile->referral_code) {
                do {
                    $referralCode = strtoupper(Str::random(8));
                } while (SubscriberProfile::where('referral_code', $referralCode)->exists());

                $profile->referral_code = $referralCode;
            }

            $pendingReferral = cache()->pull("register_referral_{$user->id}");

            if ($pendingReferral && ! $profile->referred_by) {
                $referrerProfile = SubscriberProfile::where('referral_code', $pendingReferral)->first();

                if ($referrerProfile) {
                    $profile->referred_by = $referrerProfile->user_id;

                    // Déterminer le bonus de niveau 1 depuis la config feature si activée
                    $multiLevelEnabled = FeatureSetting::isEnabled('referral_levels');
                    $referralConfig    = $multiLevelEnabled ? FeatureSetting::getConfig('referral_levels') : [];
                    $referralBonus     = $multiLevelEnabled
                        ? ($referralConfig['level_1_bonus'] ?? config('oonclick.referral_bonus', 200))
                        : config('oonclick.referral_bonus', 200);

                    // Créditer le filleul
                    $userWallet     = Wallet::firstOrCreate(['user_id' => $user->id]);
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

                    // Créditer le parrain (niveau 1)
                    $referrerWallet     = Wallet::firstOrCreate(['user_id' => $referrerProfile->user_id]);
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

                    // Enregistrer le gain de niveau 1
                    ReferralEarning::create([
                        'referrer_id' => $referrerProfile->user_id,
                        'referred_id' => $user->id,
                        'level'       => 1,
                        'amount'      => $referralBonus,
                    ]);

                    // Bonus niveau 2 (grand-parent) si la feature est activée
                    if ($multiLevelEnabled && ($referralConfig['level_2_enabled'] ?? false)) {
                        $grandparentProfile = SubscriberProfile::where('user_id', $referrerProfile->user_id)->first();

                        if ($grandparentProfile && $grandparentProfile->referred_by) {
                            $level2Bonus = $referralConfig['level_2_bonus'] ?? 50;

                            $gpWallet     = Wallet::firstOrCreate(['user_id' => $grandparentProfile->referred_by]);
                            $gpNewBalance = $gpWallet->balance + $level2Bonus;

                            WalletTransaction::create([
                                'wallet_id'     => $gpWallet->id,
                                'type'          => 'bonus',
                                'amount'        => $level2Bonus,
                                'balance_after' => $gpNewBalance,
                                'reference'     => "REFERRAL_GP_{$grandparentProfile->referred_by}_FOR_{$user->id}",
                                'description'   => "Bonus parrainage niveau 2 — filleul #{$user->id}",
                                'status'        => 'completed',
                            ]);

                            $gpWallet->balance      = $gpNewBalance;
                            $gpWallet->total_earned = $gpWallet->total_earned + $level2Bonus;
                            $gpWallet->save();

                            ReferralEarning::create([
                                'referrer_id' => $grandparentProfile->referred_by,
                                'referred_id' => $user->id,
                                'level'       => 2,
                                'amount'      => $level2Bonus,
                            ]);
                        }
                    }

                    // Incrémenter la progression des missions de parrainage
                    try {
                        app(MissionService::class)->incrementProgress($referrerProfile->user, 'referral');
                    } catch (\Throwable $e) {
                        Log::warning("MissionService incrementProgress failed for referral user#{$referrerProfile->user_id}: {$e->getMessage()}");
                    }
                }
            }

            // Persister les custom_fields si fournis (critères d'audience dynamiques)
            if ($request->has('custom_fields')) {
                $validated = $request->validate([
                    'custom_fields'   => ['nullable', 'array', 'max:20'],
                    'custom_fields.*' => ['string', 'max:255'],
                ]);
                $existing = $profile->custom_fields ?? [];
                $new      = $validated['custom_fields'] ?? [];
                $profile->custom_fields = array_merge($existing, $new);
            }

            $profile->profile_completed_at = now();
            $profile->save();

            return $profile;
        });

        try {
            $this->gamificationService->awardXp($user, 50, 'Complétion du profil');
        } catch (\Throwable $e) {
            Log::warning("GamificationService XP award failed for completeProfile user#{$user->id}: {$e->getMessage()}");
        }

        return response()->json([
            'message' => 'Profil complété.',
            'profile' => $profile,
        ], 200);
    }

    // =========================================================================
    // US-012 : Changement de numéro de téléphone
    // =========================================================================

    public function requestPhoneChange(Request $request): JsonResponse
    {
        $request->validate([
            'new_phone' => ['required', 'string', 'regex:/^[+]?[0-9]{8,15}$/'],
        ]);

        $user     = $request->user();
        $newPhone = $request->input('new_phone');

        $alreadyTaken = User::where('phone', $newPhone)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($alreadyTaken) {
            return response()->json([
                'message' => 'Ce numéro est déjà associé à un autre compte.',
            ], 409);
        }

        $code = $this->otpService->generate($newPhone, 'phone_change');
        $this->smsService->sendOtp($newPhone, $code);

        cache()->put("phone_change_{$user->id}", $newPhone, now()->addMinutes(10));

        return response()->json([
            'message' => 'Code de vérification envoyé au nouveau numéro.',
        ]);
    }

    public function confirmPhoneChange(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user     = $request->user();
        $newPhone = cache()->get("phone_change_{$user->id}");

        if (! $newPhone) {
            return response()->json([
                'message' => 'Aucune demande de changement en cours ou délai expiré.',
            ], 422);
        }

        if (! $this->otpService->verify($newPhone, $request->input('code'), 'phone_change')) {
            return response()->json([
                'message' => 'Code invalide ou expiré.',
            ], 422);
        }

        $user->phone = $newPhone;
        $user->save();

        cache()->forget("phone_change_{$user->id}");

        return response()->json([
            'message' => 'Numéro de téléphone mis à jour avec succès.',
            'phone'   => $newPhone,
        ]);
    }
}
