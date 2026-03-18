<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceFingerprint extends Model
{
    protected $fillable = [
        'user_id',
        'fingerprint_hash',
        'platform',
        'device_model',
        'os_version',
        'app_version',
        'is_trusted',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_trusted'   => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
