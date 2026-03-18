<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory;
    protected $fillable = [
        'phone',
        'code',
        'type',
        'expires_at',
        'used_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at'    => 'datetime',
            'attempts'   => 'integer',
        ];
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    public function scopeForPhone($query, string $phone, string $type)
    {
        return $query->where('phone', $phone)->where('type', $type);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }
}
