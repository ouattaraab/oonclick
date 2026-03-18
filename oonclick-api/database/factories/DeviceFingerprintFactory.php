<?php

namespace Database\Factories;

use App\Models\DeviceFingerprint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceFingerprint>
 */
class DeviceFingerprintFactory extends Factory
{
    protected $model = DeviceFingerprint::class;

    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'fingerprint_hash' => hash('sha256', str()->random(32)),
            'platform'         => 'android',
            'device_model'     => 'Samsung Galaxy A54',
            'os_version'       => 'Android 13',
            'app_version'      => '1.0.0',
            'is_trusted'       => true,
            'last_seen_at'     => now(),
        ];
    }
}
