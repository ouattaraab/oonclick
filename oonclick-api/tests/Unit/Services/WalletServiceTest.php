<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Modules\Payment\Services\WalletService;
use Database\Factories\WalletFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Crée un wallet pour un user donné via la factory directe.
 */
function createWalletFor(User $user, int $balance = 0): Wallet
{
    return Wallet::create([
        'user_id'         => $user->id,
        'balance'         => $balance,
        'pending_balance' => 0,
        'total_earned'    => $balance > 0 ? $balance : 0,
        'total_withdrawn' => 0,
    ]);
}

describe('WalletService', function () {

    it('crédite le wallet et crée une transaction', function () {
        $user = User::factory()->create();
        createWalletFor($user, 0);

        $service     = new WalletService();
        $transaction = $service->credit($user->id, 60, 'credit', 'Test crédit');

        expect($transaction)->toBeInstanceOf(WalletTransaction::class);
        expect($transaction->amount)->toBe(60);
        expect($transaction->balance_after)->toBe(60);
        expect($transaction->type)->toBe('credit');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 60,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id'     => $transaction->wallet_id,
            'type'          => 'credit',
            'amount'        => 60,
            'balance_after' => 60,
            'status'        => 'completed',
        ]);
    });

    it('incrémente total_earned pour les crédits de type credit et bonus', function () {
        $user = User::factory()->create();
        createWalletFor($user, 0);

        $service = new WalletService();
        $service->credit($user->id, 60, 'credit', 'Vue pub');
        $service->credit($user->id, 500, 'bonus', 'Bonus inscription');

        $this->assertDatabaseHas('wallets', [
            'user_id'      => $user->id,
            'balance'      => 560,
            'total_earned' => 560,
        ]);
    });

    it('débite le wallet si solde suffisant', function () {
        $user = User::factory()->create();
        createWalletFor($user, 1000);

        $service     = new WalletService();
        $transaction = $service->debit($user->id, 500, 'debit', 'Retrait test');

        expect($transaction->amount)->toBe(500);
        expect($transaction->balance_after)->toBe(500);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 500,
        ]);
    });

    it('refuse un débit si solde insuffisant', function () {
        $user = User::factory()->create();
        createWalletFor($user, 100);

        $service = new WalletService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Solde insuffisant/');

        $service->debit($user->id, 500, 'debit', 'Test solde insuffisant');
    });

    it('génère une référence unique pour chaque transaction', function () {
        $user = User::factory()->create();
        createWalletFor($user, 0);

        $service = new WalletService();
        $txn1    = $service->credit($user->id, 60, 'credit', 'Vue 1');
        $txn2    = $service->credit($user->id, 60, 'credit', 'Vue 2');

        expect($txn1->reference)->not->toBe($txn2->reference);
    });

    it('crée un wallet idempotent', function () {
        $user = User::factory()->create();

        $service = new WalletService();

        $wallet1 = $service->createWallet($user->id);
        $wallet2 = $service->createWallet($user->id);

        expect($wallet1->id)->toBe($wallet2->id);

        $this->assertDatabaseCount('wallets', 1);
    });

    it('retourne le solde courant', function () {
        $user = User::factory()->create();
        createWalletFor($user, 2500);

        $service = new WalletService();
        $balance = $service->getBalance($user->id);

        expect($balance)->toBe(2500);
    });

    it('retourne 0 si le wallet n\'existe pas', function () {
        $user = User::factory()->create();

        $service = new WalletService();
        $balance = $service->getBalance($user->id);

        expect($balance)->toBe(0);
    });

    it('accepte une référence personnalisée', function () {
        $user = User::factory()->create();
        createWalletFor($user, 0);

        $service     = new WalletService();
        $transaction = $service->credit(
            $user->id,
            60,
            'credit',
            'Test référence',
            [],
            'CUSTOM_REF_001'
        );

        expect($transaction->reference)->toBe('CUSTOM_REF_001');
    });

    it('incrémente total_withdrawn pour les débits de type debit', function () {
        $user = User::factory()->create();
        Wallet::create([
            'user_id'         => $user->id,
            'balance'         => 5000,
            'pending_balance' => 0,
            'total_earned'    => 5000,
            'total_withdrawn' => 0,
        ]);

        $service = new WalletService();
        $service->debit($user->id, 3000, 'debit', 'Retrait mobile money');

        $this->assertDatabaseHas('wallets', [
            'user_id'         => $user->id,
            'balance'         => 2000,
            'total_withdrawn' => 3000,
        ]);
    });

});
