<?php

namespace Database\Factories;

use App\Models\FraudEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FraudEvent>
 */
class FraudEventFactory extends Factory
{
    protected $model = FraudEvent::class;

    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'type'               => 'rapid_views',
            'severity'           => 'medium',
            'description'        => 'Test fraud event',
            'metadata'           => ['test' => true],
            'trust_score_impact' => -10,
            'is_resolved'        => false,
            'resolved_at'        => null,
            'resolved_by'        => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(['is_resolved' => true, 'resolved_at' => now()]);
    }

    public function critical(): static
    {
        return $this->state(['severity' => 'critical', 'trust_score_impact' => -40]);
    }
}
