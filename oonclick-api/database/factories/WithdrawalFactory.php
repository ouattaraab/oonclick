<?php

namespace Database\Factories;

use App\Models\Withdrawal;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Withdrawal>
 */
class WithdrawalFactory extends Factory
{
    protected $model = Withdrawal::class;

    public function definition(): array
    {
        return [
            'wallet_id'               => Wallet::factory(),
            'user_id'                 => User::factory(),
            'amount'                  => 5000,
            'fee'                     => 0,
            'net_amount'              => 5000,
            'mobile_operator'         => 'mtn',
            'mobile_phone'            => '+22507' . rand(10000000, 99999999),
            'paystack_reference'      => null,
            'paystack_transfer_code'  => null,
            'status'                  => 'pending',
            'failure_reason'          => null,
            'processed_at'            => null,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status'             => 'completed',
            'processed_at'       => now(),
            'paystack_reference' => 'TRF_' . str()->random(16),
        ]);
    }
}
