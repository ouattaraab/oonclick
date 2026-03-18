<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Envoie un code OTP par SMS via Africa's Talking.
     *
     * En mode local (APP_ENV=local), logue simplement le code au lieu d'envoyer.
     *
     * @param string $phone Numéro de téléphone destinataire
     * @param string $code  Code OTP à 6 chiffres en clair
     * @return bool         true si l'envoi a réussi (ou logué en local), false sinon
     */
    public function sendOtp(string $phone, string $code): bool
    {
        $message = "Votre code oon.click est : {$code}. Valable 10 minutes.";

        // En mode sandbox/local : logger le code au lieu d'envoyer
        if (app()->environment('local')) {
            Log::info("OTP [{$phone}]: {$code}");
            return true;
        }

        return $this->send($phone, $message);
    }

    /**
     * Envoie un SMS arbitraire via l'API Africa's Talking.
     *
     * @param string $phone   Numéro destinataire
     * @param string $message Contenu du SMS
     * @return bool
     */
    private function send(string $phone, string $message): bool
    {
        $apiKey   = env('AT_API_KEY');
        $username = env('AT_USERNAME', 'sandbox');

        $response = Http::withHeaders([
            'apiKey' => $apiKey,
            'Accept' => 'application/json',
        ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
            'username' => $username,
            'to'       => $phone,
            'message'  => $message,
        ]);

        if ($response->failed()) {
            Log::error('SMS failed', [
                'phone'  => $phone,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        $data = $response->json();

        // Africa's Talking retourne SMSMessageData.Recipients[].status
        $recipients = data_get($data, 'SMSMessageData.Recipients', []);

        foreach ($recipients as $recipient) {
            if (($recipient['statusCode'] ?? null) !== 101) {
                Log::error('SMS failed', [
                    'phone'     => $phone,
                    'recipient' => $recipient,
                ]);
                return false;
            }
        }

        return true;
    }
}
