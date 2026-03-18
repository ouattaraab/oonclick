<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriberProfile extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'city',
        'country',
        'operator',
        'interests',
        'referral_code',
        'referred_by',
        'profile_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'interests'             => 'array',
            'date_of_birth'         => 'date',
            'profile_completed_at'  => 'datetime',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute(): ?int
    {
        if ($this->date_of_birth === null) {
            return null;
        }

        return $this->date_of_birth->age;
    }
}
