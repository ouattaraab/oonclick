<?php

namespace App\Modules\Fraud\Jobs;

use App\Modules\Fraud\Services\TrustScoreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateTrustScoreJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives autorisées.
     * Trois tentatives pour absorber les transitoires DB.
     */
    public int $tries = 3;

    /**
     * Queue dédiée à l'anti-fraude.
     */

    public function __construct(
        private readonly int $userId,
    ) {}

    /**
     * Recalcule le trust score de l'utilisateur.
     */
    public function handle(TrustScoreService $trustScoreService): void
    {
        $newScore = $trustScoreService->recalculate($this->userId);

        Log::info('Fraud: trust score recalculé', [
            'user_id'   => $this->userId,
            'new_score' => $newScore,
        ]);
    }
}
