<?php

namespace App\Modules\Fraud\Jobs;

use App\Models\FraudEvent;
use App\Modules\Fraud\Services\TrustScoreService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FraudDetectionJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives autorisées.
     * Une seule tentative : la détection de fraude est opportuniste,
     * un échec ne doit pas saturer la queue.
     */
    public int $tries = 1;

    public function __construct(
        private readonly int $userId,
        private readonly string $type,
        private readonly string $severity,
        private readonly string $description,
        private readonly array $metadata = [],
    ) {}

    /**
     * Applique la pénalité de fraude sur l'utilisateur.
     * Vérifie d'abord l'existence d'un événement identique non résolu
     * dans les 24 dernières heures pour éviter les doublons.
     */
    public function handle(TrustScoreService $trustScoreService): void
    {
        // Déduplication : éviter d'empiler les pénalités pour le même type dans les 24h
        $alreadyExists = FraudEvent::where('user_id', $this->userId)
            ->where('type', $this->type)
            ->where('is_resolved', false)
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($alreadyExists) {
            Log::info('Fraud: événement dupliqué ignoré', [
                'user_id'  => $this->userId,
                'type'     => $this->type,
                'severity' => $this->severity,
            ]);

            return;
        }

        $event = $trustScoreService->applyPenalty(
            $this->userId,
            $this->type,
            $this->severity,
            $this->description,
            $this->metadata
        );

        Log::info('Fraud: événement enregistré', [
            'fraud_event_id' => $event->id,
            'user_id'        => $this->userId,
            'type'           => $this->type,
            'severity'       => $this->severity,
            'impact'         => $event->trust_score_impact,
        ]);
    }
}
