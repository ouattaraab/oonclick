<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCoupon extends Model
{
    protected $fillable = [
        'user_id',
        'coupon_id',
        'collected_at',
        'used_at',
        'is_used',
    ];

    protected function casts(): array
    {
        return [
            'collected_at' => 'datetime',
            'used_at'      => 'datetime',
            'is_used'      => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
