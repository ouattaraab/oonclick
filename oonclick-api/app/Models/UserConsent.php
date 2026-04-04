<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsent extends Model
{
    protected $fillable = [
        'user_id',
        'consent_type',
        'consent_version',
        'granted',
        'ip_address',
        'user_agent',
        'granted_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'granted'    => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a user has granted a specific consent.
     */
    public static function hasConsent(int $userId, string $type): bool
    {
        return static::where('user_id', $userId)
            ->where('consent_type', $type)
            ->where('granted', true)
            ->exists();
    }

    /**
     * Record (insert or update) a consent entry for a user.
     */
    public static function record(
        int $userId,
        string $type,
        bool $granted,
        ?string $ip = null,
        ?string $ua = null
    ): self {
        return static::updateOrCreate(
            ['user_id' => $userId, 'consent_type' => $type],
            [
                'granted'          => $granted,
                'consent_version'  => '1.0',
                'ip_address'       => $ip,
                'user_agent'       => $ua,
                'granted_at'       => $granted ? now() : null,
                'revoked_at'       => $granted ? null : now(),
            ]
        );
    }
}
