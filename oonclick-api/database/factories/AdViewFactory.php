<?php

namespace Database\Factories;

use App\Models\AdView;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdView>
 */
class AdViewFactory extends Factory
{
    protected $model = AdView::class;

    public function definition(): array
    {
        return [
            'campaign_id'            => Campaign::factory()->active(),
            'subscriber_id'          => User::factory()->subscriber(),
            'device_fingerprint_id'  => null,
            'started_at'             => now()->subSeconds(35),
            'completed_at'           => null,
            'watch_duration_seconds' => 0,
            'is_completed'           => false,
            'is_credited'            => false,
            'credited_at'            => null,
            'amount_credited'        => 0,
            'ip_address'             => '41.203.125.10',
            'user_agent'             => 'Flutter/oon.click',
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'completed_at'           => now(),
            'watch_duration_seconds' => 28,
            'is_completed'           => true,
        ]);
    }

    public function credited(): static
    {
        return $this->state([
            'completed_at'           => now(),
            'watch_duration_seconds' => 28,
            'is_completed'           => true,
            'is_credited'            => true,
            'credited_at'            => now(),
            'amount_credited'        => 60,
        ]);
    }
}
