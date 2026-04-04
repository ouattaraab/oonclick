<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralEarning extends Model
{
    protected $fillable = [
        'referrer_id',
        'referred_id',
        'level',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'level'  => 'integer',
            'amount' => 'integer',
        ];
    }

    // ---------------------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------------------

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }
}
