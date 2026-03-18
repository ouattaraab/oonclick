<?php

namespace Database\Factories;

use App\Models\OtpCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OtpCode>
 */
class OtpCodeFactory extends Factory
{
    protected $model = OtpCode::class;

    public function definition(): array
    {
        return [
            'phone'      => '+22507' . rand(10000000, 99999999),
            'code'       => bcrypt('123456'),
            'type'       => 'registration',
            'expires_at' => now()->addMinutes(10),
            'used_at'    => null,
            'attempts'   => 0,
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subMinutes(5)]);
    }

    public function used(): static
    {
        return $this->state(['used_at' => now()->subMinutes(2)]);
    }
}
