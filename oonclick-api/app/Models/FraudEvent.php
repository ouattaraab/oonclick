<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudEvent extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'description',
        'metadata',
        'trust_score_impact',
        'is_resolved',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata'           => 'array',
            'trust_score_impact' => 'integer',
            'is_resolved'        => 'boolean',
            'resolved_at'        => 'datetime',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
