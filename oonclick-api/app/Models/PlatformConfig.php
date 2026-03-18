<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformConfig extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_public',
    ];

    // =========================================================================
    // Static helpers
    // =========================================================================

    /**
     * Retrieve a platform config value by key, cast to its declared type.
     * The result is cached in the file driver for 60 minutes.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "platform_config.{$key}";

        return Cache::driver('file')->remember($cacheKey, now()->addMinutes(60), function () use ($key, $default) {
            $config = static::where('key', $key)->first();

            if ($config === null) {
                return $default;
            }

            return match ($config->type) {
                'integer' => (int) $config->value,
                'boolean' => filter_var($config->value, FILTER_VALIDATE_BOOLEAN),
                'json'    => json_decode($config->value, true),
                default   => $config->value,
            };
        });
    }
}
