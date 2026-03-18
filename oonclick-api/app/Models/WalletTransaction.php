<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_after',
        'reference',
        'description',
        'metadata',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'integer',
            'balance_after' => 'integer',
            'metadata'      => 'array',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
