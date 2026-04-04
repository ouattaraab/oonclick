<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    /**
     * Attributs assignables en masse.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'level',
        'document_type',
        'file_path',
        'file_disk',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
    ];

    /**
     * Casting des attributs.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level'       => 'integer',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    /**
     * Utilisateur propriétaire du document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Administrateur ayant revu le document.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Filtre les documents en attente de révision.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Filtre les documents approuvés.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Indique si le document est en attente.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Indique si le document a été approuvé.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Indique si le document a été rejeté.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
