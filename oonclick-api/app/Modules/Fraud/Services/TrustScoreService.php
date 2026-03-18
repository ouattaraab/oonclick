<?php

namespace App\Modules\Fraud\Services;

use App\Models\FraudEvent;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TrustScoreService
{
    /**
     * Recalcule le trust score d'un utilisateur à partir de 100
     * en appliquant toutes les pénalités des événements non résolus.
     *
     * @param int $userId
     * @return int Score recalculé (0–100)
     */
    public function recalculate(int $userId): int
    {
        $score  = 100;
        $events = FraudEvent::where('user_id', $userId)
            ->where('is_resolved', false)
            ->get();

        foreach ($events as $event) {
            $score += $event->trust_score_impact; // trust_score_impact est négatif
        }

        $score = max(0, min(100, $score));

        User::where('id', $userId)->update(['trust_score' => $score]);

        return $score;
    }

    /**
     * Applique une pénalité à un utilisateur en créant un FraudEvent,
     * puis recalcule son trust score. Suspend automatiquement si score <= 0.
     *
     * @param int    $userId
     * @param string $type        Type d'événement frauduleux
     * @param string $severity    Niveau de sévérité (low/medium/high/critical)
     * @param string $description Description lisible
     * @param array  $metadata    Données contextuelles supplémentaires
     * @return FraudEvent         L'événement créé
     */
    public function applyPenalty(
        int $userId,
        string $type,
        string $severity,
        string $description,
        array $metadata = []
    ): FraudEvent {
        $penalties = [
            'rapid_views'        => ['low' => -5,  'medium' => -10, 'high' => -20, 'critical' => -40],
            'multiple_accounts'  => ['low' => -15, 'medium' => -25, 'high' => -40, 'critical' => -60],
            'vpn_detected'       => ['low' => -5,  'medium' => -10, 'high' => -15, 'critical' => -20],
            'bot_behavior'       => ['low' => -10, 'medium' => -20, 'high' => -35, 'critical' => -50],
            'invalid_completion' => ['low' => -5,  'medium' => -10, 'high' => -20, 'critical' => -30],
            'suspicious_ip'      => ['low' => -3,  'medium' => -8,  'high' => -15, 'critical' => -25],
        ];

        $impact = $penalties[$type][$severity] ?? -5;

        $event = FraudEvent::create([
            'user_id'            => $userId,
            'type'               => $type,
            'severity'           => $severity,
            'description'        => $description,
            'metadata'           => $metadata,
            'trust_score_impact' => $impact,
            'is_resolved'        => false,
        ]);

        $newScore = $this->recalculate($userId);

        // Suspension automatique si le score tombe à zéro
        if ($newScore <= 0) {
            User::where('id', $userId)->update([
                'is_suspended'       => true,
                'suspension_reason'  => 'Score de confiance épuisé — suspension automatique',
            ]);

            Log::warning('Fraud: utilisateur suspendu automatiquement (score épuisé)', [
                'user_id'          => $userId,
                'fraud_event_id'   => $event->id,
                'type'             => $type,
                'severity'         => $severity,
            ]);
        }

        return $event;
    }

    /**
     * Résout un FraudEvent, recalcule le trust score et lève la suspension
     * si le nouveau score est positif.
     *
     * @param int $fraudEventId Identifiant de l'événement à résoudre
     * @param int $resolvedBy   Identifiant de l'administrateur qui résout
     * @return FraudEvent       L'événement mis à jour
     */
    public function resolve(int $fraudEventId, int $resolvedBy): FraudEvent
    {
        $event = FraudEvent::findOrFail($fraudEventId);

        $event->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
        ]);

        $newScore = $this->recalculate($event->user_id);

        // Débloquer automatiquement si le score redevient positif
        $user = User::find($event->user_id);
        if ($user && $user->is_suspended && $newScore > 0) {
            $user->update([
                'is_suspended'      => false,
                'suspension_reason' => null,
            ]);

            Log::info('Fraud: utilisateur débloqué automatiquement après résolution', [
                'user_id'        => $event->user_id,
                'fraud_event_id' => $event->id,
                'new_score'      => $newScore,
                'resolved_by'    => $resolvedBy,
            ]);
        }

        return $event->fresh();
    }
}
