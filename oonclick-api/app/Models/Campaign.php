<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'advertiser_id',
        'title',
        'description',
        'format',
        'status',
        'budget',
        'cost_per_view',
        'max_views',
        'views_count',
        'media_url',
        'media_path',
        'thumbnail_url',
        'duration_seconds',
        'targeting',
        'quiz_data',
        'end_mode',
        'starts_at',
        'ends_at',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'targeting'    => 'array',
            'quiz_data'    => 'array',
            'budget'       => 'integer',
            'cost_per_view' => 'integer',
            'max_views'    => 'integer',
            'views_count'  => 'integer',
            'starts_at'    => 'datetime',
            'ends_at'      => 'datetime',
            'approved_at'  => 'datetime',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    public function advertiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advertiser_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(CampaignTarget::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(AdView::class);
    }

    public function escrow(): HasOne
    {
        return $this->hasOne(EscrowEntry::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getRemainingViewsAttribute(): int
    {
        return $this->max_views - $this->views_count;
    }

    public function getBudgetUsedAttribute(): int
    {
        return $this->views_count * $this->cost_per_view;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canBeViewed(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        // Check end condition based on end_mode
        return match ($this->end_mode) {
            'date' => ($this->ends_at === null || ! $this->ends_at->isPast()) && $this->getRemainingViewsAttribute() > 0,
            'target_reached' => $this->getRemainingViewsAttribute() > 0,
            'manual' => $this->getRemainingViewsAttribute() > 0,
            default => $this->getRemainingViewsAttribute() > 0,
        };
    }
}
