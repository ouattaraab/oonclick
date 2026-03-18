<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->name(),
            'phone'             => '+225' . $this->faker->numerify('07########'),
            'email'             => $this->faker->unique()->safeEmail(),
            'role'              => 'subscriber',
            'kyc_level'         => 1,
            'trust_score'       => 100,
            'is_active'         => true,
            'is_suspended'      => false,
            'phone_verified_at' => now(),
            'password'          => null,
        ];
    }

    public function subscriber(): static
    {
        return $this->state(['role' => 'subscriber']);
    }

    public function advertiser(): static
    {
        return $this->state(['role' => 'advertiser']);
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function unverified(): static
    {
        return $this->state(['phone_verified_at' => null]);
    }

    public function suspended(): static
    {
        return $this->state(['is_suspended' => true, 'suspension_reason' => 'Test suspension']);
    }

    public function lowTrustScore(): static
    {
        return $this->state(['trust_score' => 20]);
    }
}
