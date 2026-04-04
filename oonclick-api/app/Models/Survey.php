<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    protected $fillable = [
        'title',
        'description',
        'reward_amount',
        'reward_xp',
        'questions',
        'is_active',
        'max_responses',
        'responses_count',
        'expires_at',
        'created_by',
        'targeting',
    ];

    protected function casts(): array
    {
        return [
            'questions'   => 'array',
            'targeting'   => 'array',
            'is_active'   => 'boolean',
            'expires_at'  => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }
}
