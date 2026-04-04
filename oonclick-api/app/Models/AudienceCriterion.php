<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AudienceCriterion extends Model
{
    protected $table = 'audience_criteria';

    protected $fillable = [
        'name', 'label', 'type', 'options', 'category',
        'is_active', 'is_required_for_profile', 'storage_column', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options'                  => 'array',
            'is_active'                => 'boolean',
            'is_required_for_profile'  => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function isBuiltin(): bool
    {
        return $this->storage_column !== null;
    }

    public static function getActiveCriteria(): \Illuminate\Support\Collection
    {
        return Cache::driver('file')->remember('audience_criteria.active', 3600, function () {
            return static::active()->get();
        });
    }

    public static function clearCache(): void
    {
        Cache::driver('file')->forget('audience_criteria.active');
    }
}
