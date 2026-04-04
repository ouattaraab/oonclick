<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerOffer extends Model
{
    protected $fillable = [
        'partner_name',
        'description',
        'logo_url',
        'cashback_percent',
        'promo_code',
        'is_active',
        'expires_at',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'cashback_percent' => 'decimal:2',
            'is_active'        => 'boolean',
            'expires_at'       => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function claims(): HasMany
    {
        return $this->hasMany(CashbackClaim::class, 'offer_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Offres actives et non expirées.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }
}
