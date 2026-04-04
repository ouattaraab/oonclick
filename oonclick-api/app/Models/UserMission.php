<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMission extends Model
{
    protected $fillable = [
        'user_id',
        'mission_slug',
        'date',
        'current_progress',
        'completed',
        'rewarded_at',
    ];

    protected function casts(): array
    {
        return [
            'completed'   => 'boolean',
            'rewarded_at' => 'datetime',
            'date'        => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
