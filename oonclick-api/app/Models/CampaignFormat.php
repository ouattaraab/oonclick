<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CampaignFormat extends Model
{
    protected $fillable = [
        'slug', 'label', 'description', 'icon', 'multiplier',
        'default_duration', 'accepted_media', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'accepted_media' => 'array',
            'multiplier'     => 'float',
            'is_active'      => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public static function getActiveFormats(): \Illuminate\Support\Collection
    {
        return Cache::driver('file')->remember('campaign_formats.active', 3600, function () {
            return static::active()->get();
        });
    }

    public static function getMultiplier(string $slug): float
    {
        return Cache::driver('file')->remember("campaign_format.multiplier.{$slug}", 3600, function () use ($slug) {
            return static::where('slug', $slug)->value('multiplier') ?? 1.0;
        });
    }

    public static function clearCache(): void
    {
        Cache::driver('file')->forget('campaign_formats.active');
        static::all()->each(fn ($f) => Cache::driver('file')->forget("campaign_format.multiplier.{$f->slug}"));
    }
}
