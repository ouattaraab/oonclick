<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant une facture (US-047).
 *
 * @property int         $id
 * @property int         $user_id
 * @property int|null    $campaign_id
 * @property string      $invoice_number
 * @property string      $type
 * @property int         $amount
 * @property int         $tax_amount
 * @property int         $total_amount
 * @property string      $status
 * @property string|null $paid_at
 * @property string|null $due_date
 * @property array|null  $metadata
 */
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campaign_id',
        'invoice_number',
        'type',
        'amount',
        'tax_amount',
        'total_amount',
        'status',
        'paid_at',
        'due_date',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'integer',
            'tax_amount'   => 'integer',
            'total_amount' => 'integer',
            'paid_at'      => 'datetime',
            'due_date'     => 'date',
            'metadata'     => 'array',
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
