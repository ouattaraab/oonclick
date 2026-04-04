<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modèle représentant un badge de gamification (US-050).
 *
 * @property int    $id
 * @property string $name
 * @property string $display_name
 * @property string $description
 * @property string $icon
 * @property int    $xp_required
 * @property int    $level
 * @property string $category
 */
class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'icon',
        'xp_required',
        'level',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'xp_required' => 'integer',
            'level'       => 'integer',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    /**
     * Utilisateurs ayant obtenu ce badge.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot('earned_at')
            ->withTimestamps();
    }
}
