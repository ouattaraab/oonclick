<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FeatureSetting extends Model
{
    protected $fillable = ['feature_slug', 'label', 'description', 'is_enabled', 'config', 'sort_order'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean', 'config' => 'array'];
    }

    public static function isEnabled(string $slug): bool
    {
        return Cache::driver('file')->remember("feature.{$slug}.enabled", 3600, function () use ($slug) {
            return static::where('feature_slug', $slug)->value('is_enabled') ?? false;
        });
    }

    public static function getConfig(string $slug): array
    {
        return Cache::driver('file')->remember("feature.{$slug}.config", 3600, function () use ($slug) {
            $raw = static::where('feature_slug', $slug)->value('config');
            if ($raw === null) return [];
            if (is_array($raw)) return $raw;
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        });
    }

    public static function getEnabled(): \Illuminate\Support\Collection
    {
        return Cache::driver('file')->remember('features.enabled', 3600, function () {
            return static::where('is_enabled', true)->orderBy('sort_order')->get();
        });
    }

    public static function clearCache(?string $slug = null): void
    {
        if ($slug) {
            Cache::driver('file')->forget("feature.{$slug}.enabled");
            Cache::driver('file')->forget("feature.{$slug}.config");
        }
        Cache::driver('file')->forget('features.enabled');
        // Also clear all individual slugs
        static::all()->each(function ($f) {
            Cache::driver('file')->forget("feature.{$f->feature_slug}.enabled");
            Cache::driver('file')->forget("feature.{$f->feature_slug}.config");
        });
    }
}
