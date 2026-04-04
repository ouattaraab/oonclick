<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashbackClaim extends Model
{
    protected $fillable = [
        'user_id',
        'offer_id',
        'purchase_amount',
        'cashback_amount',
        'receipt_reference',
        'status',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_amount'  => 'integer',
            'cashback_amount'  => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(PartnerOffer::class, 'offer_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCredited(): bool
    {
        return $this->status === 'credited';
    }
}
