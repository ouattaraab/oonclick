<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant un check-in quotidien d'un abonné.
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $checked_in_at   Date du check-in (format Y-m-d)
 * @property int    $bonus_amount    Montant crédité en FCFA
 * @property int    $streak_day      Jour dans la série consécutive
 */
class DailyCheckin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'checked_in_at',
        'bonus_amount',
        'streak_day',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'date',
            'bonus_amount'  => 'integer',
            'streak_day'    => 'integer',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
