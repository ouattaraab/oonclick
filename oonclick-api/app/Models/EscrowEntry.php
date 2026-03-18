<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscrowEntry extends Model
{
    use HasFactory;
    protected $fillable = [
        'campaign_id',
        'amount_locked',
        'amount_released',
        'platform_fees_collected',
        'amount_refunded',
        'paystack_reference',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount_locked'            => 'integer',
            'amount_released'          => 'integer',
            'platform_fees_collected'  => 'integer',
            'amount_refunded'          => 'integer',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getRemainingAttribute(): int
    {
        return $this->amount_locked - $this->amount_released - $this->amount_refunded;
    }
}
