<?php

namespace Database\Factories;

use App\Models\PlatformConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformConfig>
 */
class PlatformConfigFactory extends Factory
{
    protected $model = PlatformConfig::class;

    public function definition(): array
    {
        return [
            'key'         => 'test_config_' . str()->random(6),
            'value'       => '100',
            'type'        => 'integer',
            'description' => 'Test config',
            'is_public'   => false,
        ];
    }
}
