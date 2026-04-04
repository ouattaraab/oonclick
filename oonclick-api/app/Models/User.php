<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'role',
        'kyc_level',
        'trust_score',
        'xp_points',
        'is_active',
        'is_suspended',
        'password',
        'avatar_path',
        'company',
        'sector',
        'rccm',
        'nif',
        'website',
        'address',
        'advertiser_type',
        'birth_date',
        'city',
        'id_number',
        'company_size',
        'monthly_budget',
        'target_sectors',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active'           => 'boolean',
            'is_suspended'        => 'boolean',
            'phone_verified_at'   => 'datetime',
            'email_verified_at'   => 'datetime',
            'password'            => 'hashed',
            'birth_date'          => 'date',
            'target_sectors'      => 'array',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    public function profile(): HasOne
    {
        return $this->hasOne(SubscriberProfile::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'advertiser_id');
    }

    public function adViews(): HasMany
    {
        return $this->hasMany(AdView::class, 'subscriber_id');
    }

    public function deviceFingerprints(): HasMany
    {
        return $this->hasMany(DeviceFingerprint::class);
    }

    public function fraudEvents(): HasMany
    {
        return $this->hasMany(FraudEvent::class);
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    /**
     * Badges gagnés par cet utilisateur (US-050).
     */
    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    /**
     * Factures de l'utilisateur (US-047).
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeSubscribers($query)
    {
        return $query->where('role', 'subscriber');
    }

    public function scopeAdvertisers($query)
    {
        return $query->where('role', 'advertiser');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_suspended', false);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    public function isSubscriber(): bool
    {
        return $this->role === 'subscriber';
    }

    public function isAdvertiser(): bool
    {
        return $this->role === 'advertiser';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'advertiser') {
            return $this->role === 'advertiser';
        }

        // Panel admin
        return $this->role === 'admin'
            || $this->hasAnyRole(['super_admin', 'moderateur', 'analyste', 'support', 'comptable']);
    }

    public function isPhoneVerified(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function hasCompletedProfile(): bool
    {
        return $this->profile !== null && $this->profile->profile_completed_at !== null;
    }

    public function canWithdraw(int $amount): bool
    {
        return $this->kyc_level >= 1
            && $this->wallet !== null
            && $this->wallet->balance >= $amount;
    }
}
