<?php

namespace App\Modules\Payment\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    private string $baseUrl;
    private string $secretKey;
    private string $webhookSecret;

    public function __construct()
    {
        $this->baseUrl       = config('oonclick.paystack.base_url', 'https://api.paystack.co');
        $this->secretKey     = config('oonclick.paystack.secret_key');
        $this->webhookSecret = config('oonclick.paystack.webhook_secret');
    }

    /**
     * Initialise une transaction de paiement Paystack.
     *
     * Le montant est en FCFA (entiers). Il est multiplié par 100 pour obtenir
     * la valeur en kobo exigée par l'API Paystack.
     * Exemple : 10 000 FCFA → 1 000 000 kobo.
     *
     * @param int    $userId   Identifiant de l'utilisateur payant
     * @param int    $amount   Montant en FCFA
     * @param string $reference Référence unique de la transaction
     * @param array  $metadata Données complémentaires (campaign_id, etc.)
     * @return array           ['authorization_url', 'access_code', 'reference']
     *
     * @throws \Exception Si l'initialisation échoue
     */
    public function initializePayment(
        int $userId,
        int $amount,
        string $reference,
        array $metadata = []
    ): array {
        $user = User::findOrFail($userId);

        // Paystack exige un email ; on génère un email fictif si absent
        $email = $user->email ?? "{$user->id}@oonclick.ci";

        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'email'        => $email,
                'amount'       => $amount * 100, // FCFA → kobo
                'reference'    => $reference,
                'currency'     => 'XOF',
                'metadata'     => $metadata,
                'callback_url' => config('app.url') . '/payment/callback',
            ]);

        if ($response->failed()) {
            Log::error('Paystack initializePayment échoué', [
                'status'   => $response->status(),
                'body'     => $response->body(),
                'user_id'  => $userId,
                'reference' => $reference,
            ]);

            throw new \Exception('Initialisation du paiement Paystack échouée.');
        }

        $data = $response->json('data');

        return [
            'authorization_url' => $data['authorization_url'],
            'access_code'       => $data['access_code'],
            'reference'         => $data['reference'],
        ];
    }

    /**
     * Vérifie le statut d'une transaction via son référence.
     *
     * @param string $reference Référence Paystack de la transaction
     * @return array            Tableau 'data' complet retourné par Paystack
     *
     * @throws \Exception Si la vérification HTTP échoue
     */
    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if ($response->failed()) {
            Log::error('Paystack verifyTransaction échoué', [
                'reference' => $reference,
                'status'    => $response->status(),
                'body'      => $response->body(),
            ]);

            throw new \Exception('Vérification Paystack échouée.');
        }

        return $response->json('data');
    }

    /**
     * Vérifie la signature HMAC-SHA512 d'un webhook Paystack.
     *
     * La signature est fournie dans le header X-Paystack-Signature et doit
     * correspondre au HMAC-SHA512 du corps brut de la requête signé avec
     * la clé secrète webhook.
     *
     * @param string $payload   Corps brut de la requête (getContent())
     * @param string $signature Valeur du header x-paystack-signature
     * @return bool             true si la signature est valide
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $computed = hash_hmac('sha512', $payload, $this->webhookSecret);

        return hash_equals($computed, $signature);
    }

    /**
     * Initie un transfert sortant vers un bénéficiaire mobile money.
     *
     * Utilisé pour les retraits des abonnés vers leur compte mobile money.
     * Le montant est converti de FCFA en kobo (× 100).
     *
     * @param int    $amount        Montant en FCFA
     * @param string $recipientCode Code du bénéficiaire Paystack
     * @param string $reference     Référence unique du transfert
     * @return array                ['transfer_code', 'status']
     *
     * @throws \Exception Si le transfert échoue
     */
    public function initializeTransfer(
        int $amount,
        string $recipientCode,
        string $reference
    ): array {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transfer", [
                'source'    => 'balance',
                'amount'    => $amount * 100, // FCFA → kobo
                'recipient' => $recipientCode,
                'reference' => $reference,
                'reason'    => 'Retrait oon.click',
            ]);

        if ($response->failed()) {
            Log::error('Paystack initializeTransfer échoué', [
                'reference'      => $reference,
                'recipient_code' => $recipientCode,
                'amount'         => $amount,
                'status'         => $response->status(),
                'body'           => $response->body(),
            ]);

            throw new \Exception('Initialisation du transfert Paystack échouée.');
        }

        $data = $response->json('data');

        return [
            'transfer_code' => $data['transfer_code'],
            'status'        => $data['status'],
        ];
    }

    /**
     * Crée un bénéficiaire de transfert (mobile money) dans Paystack.
     *
     * Les codes opérateurs en Côte d'Ivoire :
     *   MTN   → 'MTN'
     *   MOOV  → 'MOOV'
     *   Orange → 'AIRTEL' (code Paystack CI)
     *
     * @param string $name          Nom complet du bénéficiaire
     * @param string $accountNumber Numéro de téléphone mobile money
     * @param string $bankCode      Code opérateur Paystack (MTN, MOOV, AIRTEL)
     * @return string               recipient_code retourné par Paystack
     *
     * @throws \Exception Si la création du bénéficiaire échoue
     */
    public function createTransferRecipient(
        string $name,
        string $accountNumber,
        string $bankCode
    ): string {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transferrecipient", [
                'type'           => 'mobile_money',
                'name'           => $name,
                'account_number' => $accountNumber,
                'bank_code'      => $bankCode,
                'currency'       => 'XOF',
            ]);

        if ($response->failed()) {
            Log::error('Paystack createTransferRecipient échoué', [
                'name'           => $name,
                'account_number' => $accountNumber,
                'bank_code'      => $bankCode,
                'status'         => $response->status(),
                'body'           => $response->body(),
            ]);

            throw new \Exception('Création du bénéficiaire Paystack échouée.');
        }

        return $response->json('data.recipient_code');
    }

    /**
     * Retourne le code opérateur Paystack correspondant à l'opérateur local CI.
     *
     * @param string $operator Opérateur local : 'mtn', 'moov', 'orange'
     * @return string          Code Paystack : 'MTN', 'MOOV', 'AIRTEL'
     *
     * @throws \InvalidArgumentException Si l'opérateur est inconnu
     */
    public function getMobileOperatorCode(string $operator): string
    {
        $mapping = [
            'mtn'    => 'MTN',
            'moov'   => 'MOOV',
            'orange' => 'AIRTEL',
        ];

        $operator = strtolower($operator);

        if (! array_key_exists($operator, $mapping)) {
            throw new \InvalidArgumentException(
                "Opérateur mobile inconnu : {$operator}. Valeurs acceptées : mtn, moov, orange."
            );
        }

        return $mapping[$operator];
    }
}
