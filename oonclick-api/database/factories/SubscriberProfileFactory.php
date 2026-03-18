<?php

namespace Database\Factories;

use App\Models\SubscriberProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriberProfile>
 */
class SubscriberProfileFactory extends Factory
{
    protected $model = SubscriberProfile::class;

    public function definition(): array
    {
        $cities = ['Abidjan', 'Bouaké', 'Daloa', 'San-Pédro', 'Yamoussoukro', 'Korhogo'];

        return [
            'user_id'              => User::factory(),
            'first_name'           => $this->faker->firstName(),
            'last_name'            => $this->faker->lastName(),
            'gender'               => $this->faker->randomElement(['male', 'female']),
            'date_of_birth'        => $this->faker->dateTimeBetween('-40 years', '-18 years')->format('Y-m-d'),
            'city'                 => $this->faker->randomElement($cities),
            'country'              => 'CI',
            'operator'             => $this->faker->randomElement(['mtn', 'moov', 'orange']),
            'interests'            => $this->faker->randomElements(['sport', 'musique', 'mode', 'tech', 'cuisine', 'voyage'], 3),
            'referral_code'        => strtoupper($this->faker->lexify('????????')),
            'profile_completed_at' => now(),
        ];
    }

    public function incomplete(): static
    {
        return $this->state(['profile_completed_at' => null]);
    }
}
