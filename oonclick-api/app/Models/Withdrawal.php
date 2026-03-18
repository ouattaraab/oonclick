<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    use HasFactory;
    protected $fillable = [
        'wallet_id',
        'user_id',
        'amount',
        'fee',
        'net_amount',
        'mobile_operator',
        'mobile_phone',
        'paystack_reference',
        'paystack_transfer_code',
        'status',
        'failure_reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'integer',
            'fee'          => 'integer',
            'net_amount'   => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
