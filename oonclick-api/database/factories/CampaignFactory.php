<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $budget = $this->faker->randomElement([5000, 10000, 20000, 50000]);
        $costPerView = 100;

        return [
            'advertiser_id'    => User::factory()->advertiser(),
            'title'            => $this->faker->sentence(4),
            'description'      => $this->faker->paragraph(),
            'format'           => $this->faker->randomElement(['video', 'scratch', 'quiz', 'flash']),
            'status'           => 'draft',
            'budget'           => $budget,
            'cost_per_view'    => $costPerView,
            'max_views'        => (int) floor($budget / $costPerView),
            'views_count'      => 0,
            'media_url'        => 'https://cdn.oon.click/test/video.mp4',
            'media_path'       => 'campaigns/test/video.mp4',
            'thumbnail_url'    => 'https://cdn.oon.click/test/thumb.jpg',
            'duration_seconds' => 30,
            'targeting'        => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function pendingReview(): static
    {
        return $this->state(['status' => 'pending_review', 'media_url' => 'https://cdn.oon.click/test/video.mp4']);
    }

    public function withTargeting(array $targeting): static
    {
        return $this->state(['targeting' => $targeting]);
    }

    public function full(): static
    {
        return $this->state(fn (array $attrs) => ['views_count' => $attrs['max_views']]);
    }
}
