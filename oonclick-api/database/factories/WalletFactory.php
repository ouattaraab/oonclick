<?php

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'balance'         => 0,
            'pending_balance' => 0,
            'total_earned'    => 0,
            'total_withdrawn' => 0,
        ];
    }

    public function withBalance(int $amount): static
    {
        return $this->state(['balance' => $amount, 'total_earned' => $amount]);
    }
}
