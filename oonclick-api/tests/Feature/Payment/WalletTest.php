<?php

use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Modules\Payment\Jobs\ProcessWithdrawalJob;
use Illuminate\Support\Facades\Queue;

describe('Wallet', function () {

    it('retourne le solde et les transactions', function () {
        $subscriber = makeSubscriber();

        // Remplacer le wallet créé par makeSubscriber par un avec balance=1000
        Wallet::where('user_id', $subscriber->id)->forceDelete();
        Wallet::factory()->withBalance(1000)->create(['user_id' => $subscriber->id]);

        $response = $this->actingAs($subscriber)->getJson('/api/wallet');

        $response->assertStatus(200);
        $response->assertJsonPath('wallet.balance', 1000);
        $response->assertJsonStructure(['wallet', 'recent_transactions']);
    });

    it('permet un retrait si solde suffisant et kyc_level >= 1', function () {
        Queue::fake();

        $subscriber = makeSubscriber(['kyc_level' => 1]);

        Wallet::where('user_id', $subscriber->id)->forceDelete();
        Wallet::factory()->withBalance(10000)->create(['user_id' => $subscriber->id]);

        $response = $this->actingAs($subscriber)
            ->postJson('/api/wallet/withdraw', [
                'amount'          => 5000,
                'mobile_operator' => 'mtn',
                'mobile_phone'    => '+22507' . rand(10000000, 99999999),
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Votre demande de retrait a été soumise.');
        $response->assertJsonPath('withdrawal.amount', 5000);
        $response->assertJsonPath('withdrawal.status', 'pending');

        $this->assertDatabaseHas('withdrawals', [
            'user_id' => $subscriber->id,
            'amount'  => 5000,
            'status'  => 'pending',
        ]);

        // Le wallet doit être débité
        $this->assertDatabaseHas('wallets', [
            'user_id' => $subscriber->id,
            'balance' => 5000,
        ]);

        // Vérifier que le job a été dispatché
        Queue::assertPushed(ProcessWithdrawalJob::class);
    });

    it('refuse si solde insuffisant', function () {
        $subscriber = makeSubscriber(['kyc_level' => 1]);

        Wallet::where('user_id', $subscriber->id)->forceDelete();
        Wallet::factory()->withBalance(1000)->create(['user_id' => $subscriber->id]);

        $response = $this->actingAs($subscriber)
            ->postJson('/api/wallet/withdraw', [
                'amount'          => 5000,
                'mobile_operator' => 'mtn',
                'mobile_phone'    => '+22507' . rand(10000000, 99999999),
            ]);

        $response->assertStatus(422);

        $message = $response->json('message');
        $this->assertStringContainsStringIgnoringCase('Solde insuffisant', $message);
    });

    it('refuse si kyc_level=0', function () {
        $subscriber = makeSubscriber(['kyc_level' => 0]);

        Wallet::where('user_id', $subscriber->id)->forceDelete();
        Wallet::factory()->withBalance(10000)->create(['user_id' => $subscriber->id]);

        $response = $this->actingAs($subscriber)
            ->postJson('/api/wallet/withdraw', [
                'amount'          => 5000,
                'mobile_operator' => 'mtn',
                'mobile_phone'    => '+22507' . rand(10000000, 99999999),
            ]);

        $response->assertStatus(403);
    });

    it('refuse si montant inférieur au minimum', function () {
        config(['oonclick.min_withdrawal' => 5000]);

        $subscriber = makeSubscriber(['kyc_level' => 1]);

        Wallet::where('user_id', $subscriber->id)->forceDelete();
        Wallet::factory()->withBalance(10000)->create(['user_id' => $subscriber->id]);

        $response = $this->actingAs($subscriber)
            ->postJson('/api/wallet/withdraw', [
                'amount'          => 1000,
                'mobile_operator' => 'mtn',
                'mobile_phone'    => '+22507' . rand(10000000, 99999999),
            ]);

        $response->assertStatus(422);
    });

    it('retourne 404 si wallet introuvable', function () {
        $subscriber = makeSubscriber();
        Wallet::where('user_id', $subscriber->id)->forceDelete();

        $response = $this->actingAs($subscriber)->getJson('/api/wallet');

        $response->assertStatus(404);
    });

    it('refuse si non authentifié', function () {
        $response = $this->getJson('/api/wallet');
        $response->assertStatus(401);
    });

    it('refuse un opérateur invalide', function () {
        $subscriber = makeSubscriber(['kyc_level' => 1]);

        Wallet::where('user_id', $subscriber->id)->forceDelete();
        Wallet::factory()->withBalance(10000)->create(['user_id' => $subscriber->id]);

        $response = $this->actingAs($subscriber)
            ->postJson('/api/wallet/withdraw', [
                'amount'          => 5000,
                'mobile_operator' => 'wave',
                'mobile_phone'    => '+22507' . rand(10000000, 99999999),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrorFor('mobile_operator');
    });

    it('retourne l\'historique paginé des transactions', function () {
        $subscriber = makeSubscriber();

        $wallet = Wallet::where('user_id', $subscriber->id)->first();

        \App\Models\WalletTransaction::factory()->count(5)->create([
            'wallet_id' => $wallet->id,
            'status'    => 'completed',
        ]);

        $response = $this->actingAs($subscriber)->getJson('/api/wallet/transactions');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'current_page', 'total']);
    });

});
