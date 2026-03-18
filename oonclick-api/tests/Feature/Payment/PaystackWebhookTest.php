<?php

use App\Modules\Campaign\Jobs\ProcessCampaignPaymentJob;
use Illuminate\Support\Facades\Queue;

describe('POST /api/paystack/webhook', function () {

    beforeEach(function () {
        // Définir un secret webhook connu pour les tests
        config(['oonclick.paystack.webhook_secret' => 'test_webhook_secret_key']);
    });

    /**
     * Génère une signature HMAC-SHA512 valide pour le payload donné.
     */
    $validSignature = function (string $payload): string {
        return hash_hmac('sha512', $payload, 'test_webhook_secret_key');
    };

    it('rejette une signature invalide', function () {
        $payload = json_encode([
            'event' => 'charge.success',
            'data'  => [],
        ]);

        $response = $this->postJson('/api/paystack/webhook', json_decode($payload, true), [
            'x-paystack-signature' => 'signature_completement_invalide',
            'Content-Type'         => 'application/json',
        ]);

        $response->assertStatus(401);
    });

    it('rejette sans signature', function () {
        $payload = json_encode([
            'event' => 'charge.success',
            'data'  => [],
        ]);

        $response = $this->postJson('/api/paystack/webhook', json_decode($payload, true));

        $response->assertStatus(401);
    });

    it('traite un événement charge.success', function () {
        Queue::fake();

        $advertiser = makeAdvertiser();
        $campaign   = makeActiveCampaign($advertiser);

        $payloadData = [
            'event' => 'charge.success',
            'data'  => [
                'reference' => 'PAY_TEST_REF_001',
                'metadata'  => [
                    'campaign_id' => $campaign->id,
                ],
            ],
        ];

        $rawPayload = json_encode($payloadData);
        $signature  = hash_hmac('sha512', $rawPayload, 'test_webhook_secret_key');

        $response = $this->call(
            'POST',
            '/api/paystack/webhook',
            [],
            [],
            [],
            [
                'HTTP_X-PAYSTACK-SIGNATURE' => $signature,
                'CONTENT_TYPE'              => 'application/json',
            ],
            $rawPayload
        );

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'OK');

        Queue::assertPushed(ProcessCampaignPaymentJob::class, function ($job) use ($campaign) {
            return true; // Le job a bien été dispatché
        });
    });

    it('retourne 200 même pour un événement inconnu', function () {
        $payloadData = [
            'event' => 'unknown.event',
            'data'  => [],
        ];

        $rawPayload = json_encode($payloadData);
        $signature  = hash_hmac('sha512', $rawPayload, 'test_webhook_secret_key');

        $response = $this->call(
            'POST',
            '/api/paystack/webhook',
            [],
            [],
            [],
            [
                'HTTP_X-PAYSTACK-SIGNATURE' => $signature,
                'CONTENT_TYPE'              => 'application/json',
            ],
            $rawPayload
        );

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'OK');
    });

    it('traite un événement transfer.success', function () {
        Queue::fake();

        $subscriber = makeSubscriber();
        $wallet     = \App\Models\Wallet::where('user_id', $subscriber->id)->first();

        $withdrawal = \App\Models\Withdrawal::factory()->create([
            'user_id'              => $subscriber->id,
            'wallet_id'            => $wallet->id,
            'amount'               => 5000,
            'status'               => 'pending',
            'paystack_transfer_code' => 'TRF_TEST001',
        ]);

        $payloadData = [
            'event' => 'transfer.success',
            'data'  => [
                'transfer_code' => 'TRF_TEST001',
                'metadata'      => [
                    'withdrawal_id' => $withdrawal->id,
                ],
            ],
        ];

        $rawPayload = json_encode($payloadData);
        $signature  = hash_hmac('sha512', $rawPayload, 'test_webhook_secret_key');

        $response = $this->call(
            'POST',
            '/api/paystack/webhook',
            [],
            [],
            [],
            [
                'HTTP_X-PAYSTACK-SIGNATURE' => $signature,
                'CONTENT_TYPE'              => 'application/json',
            ],
            $rawPayload
        );

        $response->assertStatus(200);
    });

});
