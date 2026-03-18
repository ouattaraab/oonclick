<?php

namespace Database\Factories;

use App\Models\EscrowEntry;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EscrowEntry>
 */
class EscrowEntryFactory extends Factory
{
    protected $model = EscrowEntry::class;

    public function definition(): array
    {
        return [
            'campaign_id'             => Campaign::factory(),
            'amount_locked'           => 10000,
            'amount_released'         => 0,
            'platform_fees_collected' => 0,
            'amount_refunded'         => 0,
            'paystack_reference'      => 'PAY_' . strtoupper(str()->random(16)),
            'status'                  => 'locked',
        ];
    }

    public function partial(): static
    {
        return $this->state(['amount_released' => 2400, 'platform_fees_collected' => 1600, 'status' => 'partial']);
    }
}
