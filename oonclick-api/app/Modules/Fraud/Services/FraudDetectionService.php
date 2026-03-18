<?php

namespace App\Modules\Fraud\Services;

use App\Models\AdView;
use App\Models\DeviceFingerprint;
use App\Modules\Fraud\Jobs\FraudDetectionJob;
use Illuminate\Support\Facades\Log;

class FraudDetectionService
{
    public function __construct(
        private readonly TrustScoreService $trustScoreService,
    ) {}

    /**
     * Analyse une vue après complétion et dispatche des jobs anti-fraude
     * si des anomalies sont détectées.
     *
     * Cette méthode ne bloque jamais la réponse HTTP — tout est asynchrone.
     *
     * @param AdView $adView Vue complétée à analyser
     */
    public function analyzeView(AdView $adView): void
    {
        $subscriberId = $adView->subscriber_id;

        // 1. Vérification vitesse : trop de vues en 5 minutes
        $recentViewsCount = AdView::where('subscriber_id', $subscriberId)
            ->where('started_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentViewsCount >= 3) {
            FraudDetectionJob::dispatch(
                $subscriberId,
                'rapid_views',
                'medium',
                'Visionnages trop rapides détectés',
                [
                    'ad_view_id'          => $adView->id,
                    'views_last_5_minutes' => $recentViewsCount,
                ]
            )->onQueue('fraud');
        }

        // 2. Vérification IP suspecte : même IP utilisée par plusieurs comptes dans les 24h
        if ($adView->ip_address !== null) {
            $distinctUsersOnSameIp = AdView::where('ip_address', $adView->ip_address)
                ->where('started_at', '>=', now()->subDay())
                ->distinct('subscriber_id')
                ->count('subscriber_id');

            if ($distinctUsersOnSameIp >= 3) {
                FraudDetectionJob::dispatch(
                    $subscriberId,
                    'suspicious_ip',
                    'medium',
                    'Adresse IP partagée par plusieurs comptes',
                    [
                        'ad_view_id'             => $adView->id,
                        'distinct_users_on_ip'   => $distinctUsersOnSameIp,
                    ]
                )->onQueue('fraud');
            }
        }

        // 3. Vérification device multi-comptes
        if ($adView->device_fingerprint_id !== null) {
            $fingerprint = DeviceFingerprint::find($adView->device_fingerprint_id);

            if ($fingerprint !== null) {
                $sharedDevice = DeviceFingerprint::where('fingerprint_hash', $fingerprint->fingerprint_hash)
                    ->where('user_id', '!=', $subscriberId)
                    ->exists();

                if ($sharedDevice) {
                    FraudDetectionJob::dispatch(
                        $subscriberId,
                        'multiple_accounts',
                        'high',
                        'Appareil déjà associé à un autre compte',
                        [
                            'ad_view_id'        => $adView->id,
                            'fingerprint_hash'  => $fingerprint->fingerprint_hash,
                        ]
                    )->onQueue('fraud');
                }
            }
        }

        // 4. Vérification durée aberrante (possible bot)
        $campaign = $adView->campaign;

        if (
            $campaign !== null
            && $campaign->duration_seconds > 0
            && $adView->watch_duration_seconds !== null
            && $adView->watch_duration_seconds > ($campaign->duration_seconds * 3)
        ) {
            FraudDetectionJob::dispatch(
                $subscriberId,
                'bot_behavior',
                'medium',
                'Durée aberrante détectée',
                [
                    'ad_view_id'              => $adView->id,
                    'watch_duration_seconds'  => $adView->watch_duration_seconds,
                    'campaign_duration_seconds' => $campaign->duration_seconds,
                ]
            )->onQueue('fraud');
        }
    }

    /**
     * Vérifie si un device fingerprint appartient déjà à un autre compte.
     * Appelé lors de l'inscription ou de la vérification OTP.
     *
     * @param string $fingerprintHash Hash du device à vérifier
     * @param int    $userId          Utilisateur en cours d'inscription
     * @return bool  true si suspect (hash déjà connu d'un autre user), false sinon
     */
    public function checkDeviceOnRegister(string $fingerprintHash, int $userId): bool
    {
        $existsOnOtherAccount = DeviceFingerprint::where('fingerprint_hash', $fingerprintHash)
            ->where('user_id', '!=', $userId)
            ->exists();

        if ($existsOnOtherAccount) {
            $this->trustScoreService->applyPenalty(
                $userId,
                'multiple_accounts',
                'high',
                'Device déjà associé à un autre compte',
                ['fingerprint_hash' => $fingerprintHash]
            );

            Log::warning('Fraud: device fingerprint partagé détecté à l\'inscription', [
                'user_id'          => $userId,
                'fingerprint_hash' => $fingerprintHash,
            ]);

            return true;
        }

        return false;
    }
}
