<?php

namespace App\Modules\Auth\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    /**
     * Génère un code OTP à 6 chiffres pour le numéro de téléphone donné.
     *
     * @param string $phone Numéro de téléphone (ex : +22507XXXXXXXX)
     * @param string $type  Type d'OTP : 'registration' | 'login' | 'withdrawal' | 'kyc'
     * @return string       Code OTP à 6 chiffres en clair
     */
    public function generate(string $phone, string $type): string
    {
        // 1. Invalider les OTPs actifs existants pour (phone, type)
        OtpCode::where('phone', $phone)
            ->where('type', $type)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        // 2. Générer un code aléatoire à 6 chiffres
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // 3. Persister le code hashé avec son expiration
        OtpCode::create([
            'phone'      => $phone,
            'type'       => $type,
            'code'       => Hash::make($code),
            'expires_at' => now()->addMinutes(config('oonclick.otp_expires_minutes')),
            'used_at'    => null,
            'attempts'   => 0,
        ]);

        // 4. Retourner le code en clair pour l'envoi SMS
        return $code;
    }

    /**
     * Vérifie qu'un code OTP est valide pour le numéro et le type donnés.
     *
     * @param string $phone Numéro de téléphone
     * @param string $code  Code OTP saisi par l'utilisateur
     * @param string $type  Type d'OTP
     * @return bool         true si le code est correct et non expiré, false sinon
     */
    public function verify(string $phone, string $code, string $type): bool
    {
        // 1. Trouver l'OTP actif (non utilisé et non expiré)
        $otp = OtpCode::where('phone', $phone)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return false;
        }

        // 2. Incrémenter le compteur de tentatives
        $otp->attempts += 1;

        // 3. Vérifier le nombre maximum de tentatives
        if ($otp->attempts > config('oonclick.otp_max_attempts')) {
            $otp->save();
            return false;
        }

        // 4. Vérifier le code par rapport au hash stocké
        if (! Hash::check($code, $otp->code)) {
            $otp->save();
            return false;
        }

        // 5. Code valide : marquer comme utilisé
        $otp->used_at = now();
        $otp->save();

        return true;
    }
}
