<?php

namespace Database\Factories;

use App\Models\WalletTransaction;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    protected $model = WalletTransaction::class;

    public function definition(): array
    {
        return [
            'wallet_id'     => Wallet::factory(),
            'type'          => 'credit',
            'amount'        => 60,
            'balance_after' => 60,
            'reference'     => 'TXN_' . strtoupper(str()->random(12)),
            'description'   => 'Test transaction',
            'metadata'      => null,
            'status'        => 'completed',
        ];
    }
}
