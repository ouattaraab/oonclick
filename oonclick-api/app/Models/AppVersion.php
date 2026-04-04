<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $fillable = [
        'platform',
        'latest_version',
        'min_version',
        'force_update',
        'store_url',
        'release_notes',
    ];

    protected function casts(): array
    {
        return [
            'force_update' => 'boolean',
        ];
    }

    public static function forPlatform(string $platform): ?self
    {
        return static::where('platform', $platform)->first();
    }

    public function requiresUpdate(string $currentVersion): bool
    {
        return version_compare($currentVersion, $this->min_version, '<');
    }

    public function isForced(string $currentVersion): bool
    {
        return $this->force_update && $this->requiresUpdate($currentVersion);
    }
}
