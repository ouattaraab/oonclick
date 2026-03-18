<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdView extends Model
{
    use HasFactory;
    protected $fillable = [
        'campaign_id',
        'subscriber_id',
        'device_fingerprint_id',
        'started_at',
        'completed_at',
        'watch_duration_seconds',
        'is_completed',
        'is_credited',
        'credited_at',
        'amount_credited',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'is_completed'   => 'boolean',
            'is_credited'    => 'boolean',
            'started_at'     => 'datetime',
            'completed_at'   => 'datetime',
            'credited_at'    => 'datetime',
            'amount_credited' => 'integer',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subscriber_id');
    }

    public function deviceFingerprint(): BelongsTo
    {
        return $this->belongsTo(DeviceFingerprint::class);
    }
}
