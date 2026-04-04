<?php

namespace App\Modules\Diffusion\Services;

use App\Models\AdView;
use App\Models\Campaign;
use App\Models\CampaignFormat;
use App\Models\CampaignTarget;
use App\Models\Coupon;
use App\Models\DeviceFingerprint;
use App\Models\FeatureSetting;
use App\Models\User;
use App\Models\UserCoupon;
use App\Modules\Diffusion\Jobs\CompleteCampaignJob;
use App\Modules\Payment\Services\EscrowService;
use App\Modules\Payment\Services\WalletService;
use App\Services\GamificationService;
use App\Services\MissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ViewTrackingService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly EscrowService $escrowService,
        private readonly GamificationService $gamificationService,
    ) {}

    /**
     * Vérifie si un abonné est autorisé à visionner une campagne.
     *
     * Les vérifications sont réalisées dans l'ordre suivant :
     *   1. Compte actif et non suspendu
     *   2. Profil complété
     *   3. Trust score suffisant
     *   4. Vue déjà complétée pour cette campagne
     *   5. Limite horaire non atteinte
     *   6. Limite journalière non atteinte
     *   7. Campagne encore diffusable
     *
     * @param User     $subscriber Abonné à vérifier
     * @param Campaign $campaign   Campagne cible
     * @return array{allowed: bool, reason: string|null}
     */
    public function canView(User $subscriber, Campaign $campaign): array
    {
        // 1. Compte actif et non suspendu
        if (! $subscriber->is_active || $subscriber->is_suspended) {
            return ['allowed' => false, 'reason' => 'Compte suspendu'];
        }

        // 2. Profil complété
        if ($subscriber->profile === null || $subscriber->profile->profile_completed_at === null) {
            return ['allowed' => false, 'reason' => 'Profil incomplet'];
        }

        // 3. Trust score suffisant
        $minTrustScore = config('oonclick.min_trust_score', 40);
        if ($subscriber->trust_score < $minTrustScore) {
            return ['allowed' => false, 'reason' => 'Score de confiance insuffisant'];
        }

        // 4. Vue déjà complétée pour cette campagne
        $alreadyViewed = AdView::where('campaign_id', $campaign->id)
            ->where('subscriber_id', $subscriber->id)
            ->where('is_completed', true)
            ->exists();

        if ($alreadyViewed) {
            return ['allowed' => false, 'reason' => 'Pub déjà visionnée'];
        }

        // 5. Limite horaire
        $maxViewsPerHour = config('oonclick.max_views_per_hour', 10);
        $viewsLastHour   = AdView::where('subscriber_id', $subscriber->id)
            ->where('started_at', '>=', now()->subHour())
            ->count();

        if ($viewsLastHour >= $maxViewsPerHour) {
            return ['allowed' => false, 'reason' => 'Limite horaire atteinte'];
        }

        // 6. Limite journalière — augmentée selon le niveau si la feature 'levels' est activée
        if (FeatureSetting::isEnabled('levels')) {
            $levelConfig    = FeatureSetting::getConfig('levels');
            $userLevel      = $this->gamificationService->getUserLevel($subscriber);
            $levelData      = collect($levelConfig['levels'] ?? [])->firstWhere('level', $userLevel);
            $maxViewsPerDay = $levelData['max_views'] ?? config('oonclick.max_views_per_day', 30);
        } else {
            $maxViewsPerDay = config('oonclick.max_views_per_day', 30);
        }

        $viewsLastDay = AdView::where('subscriber_id', $subscriber->id)
            ->where('started_at', '>=', now()->subDay())
            ->count();

        if ($viewsLastDay >= $maxViewsPerDay) {
            return ['allowed' => false, 'reason' => 'Limite journalière atteinte'];
        }

        // 7. Campagne encore active et diffusable
        if (! $campaign->canBeViewed()) {
            return ['allowed' => false, 'reason' => 'Campagne non disponible'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Enregistre le début d'une session de visionnage.
     *
     * Crée un enregistrement AdView et met à jour ou crée le DeviceFingerprint
     * si les données d'appareil sont fournies. Tout se passe dans une transaction
     * pour garantir la cohérence.
     *
     * @param User     $subscriber  Abonné démarrant la vue
     * @param Campaign $campaign    Campagne visionnée
     * @param array    $deviceData  Données optionnelles : device_fingerprint, platform, device_model
     * @return AdView               Vue créée
     */
    public function startView(User $subscriber, Campaign $campaign, array $deviceData = []): AdView
    {
        return DB::transaction(function () use ($subscriber, $campaign, $deviceData) {
            $deviceFingerprintId = null;

            // Résoudre le DeviceFingerprint si un hash est fourni
            if (! empty($deviceData['device_fingerprint'])) {
                $fingerprint = DeviceFingerprint::firstOrCreate(
                    [
                        'user_id'          => $subscriber->id,
                        'fingerprint_hash' => $deviceData['device_fingerprint'],
                    ],
                    [
                        'platform'     => $deviceData['platform'] ?? null,
                        'device_model' => $deviceData['device_model'] ?? null,
                        'is_trusted'   => false,
                        'last_seen_at' => now(),
                    ]
                );

                // Mettre à jour last_seen_at à chaque apparition
                $fingerprint->last_seen_at = now();
                $fingerprint->save();

                $deviceFingerprintId = $fingerprint->id;
            }

            $adView = AdView::create([
                'campaign_id'           => $campaign->id,
                'subscriber_id'         => $subscriber->id,
                'device_fingerprint_id' => $deviceFingerprintId,
                'started_at'            => now(),
                'is_completed'          => false,
                'is_credited'           => false,
                'ip_address'            => request()->ip(),
                'user_agent'            => request()->userAgent(),
            ]);

            return $adView;
        });
    }

    /**
     * Complète une session de visionnage et crédite l'abonné si éligible.
     *
     * Étapes :
     *   1. Vérifier que la vue n'est pas déjà complétée (idempotence)
     *   2. Calculer le pourcentage visionné vs la durée de la campagne
     *   3. Vérifier le seuil minimum de visionnage
     *   4. Calculer le montant selon le multiplicateur de format
     *   5. Dans une transaction :
     *      - Marquer l'AdView complétée et créditée
     *      - Incrémenter views_count de la campagne (lockForUpdate)
     *      - Libérer l'escrow (abonné + plateforme)
     *      - Créditer le wallet de l'abonné
     *      - Mettre à jour CampaignTarget si existant
     *      - Dispatcher CompleteCampaignJob si quota atteint
     *
     * @param AdView $adView               Vue à compléter
     * @param int    $watchDurationSeconds Durée effective de visionnage en secondes
     * @return array{credited: bool, amount: int, reason: string|null}
     */
    public function completeView(AdView $adView, int $watchDurationSeconds): array
    {
        // 1. Idempotence : vue déjà complétée
        if ($adView->is_completed) {
            return ['credited' => false, 'amount' => 0, 'reason' => 'Vue déjà complétée'];
        }

        $campaign = $adView->campaign;

        // 2. Calculer le pourcentage regardé
        $minWatchPercent = config('oonclick.min_watch_percent', 80);
        $percent         = 100; // Par défaut si durée inconnue

        if ($campaign->duration_seconds > 0) {
            $percent = ($watchDurationSeconds / $campaign->duration_seconds) * 100;
        } else {
            // Durée inconnue : valider uniquement une durée minimale de 5 secondes
            if ($watchDurationSeconds < 5) {
                $this->markViewIncomplete($adView, $watchDurationSeconds);

                return ['credited' => false, 'amount' => 0, 'reason' => 'Durée de visionnage insuffisante'];
            }
        }

        // 3. Seuil minimum de visionnage
        if ($percent < $minWatchPercent) {
            $this->markViewIncomplete($adView, $watchDurationSeconds);

            return ['credited' => false, 'amount' => 0, 'reason' => 'Durée de visionnage insuffisante'];
        }

        // 4. Calculer le montant selon le multiplicateur de format (depuis la base de données)
        $formatMultiplier = CampaignFormat::getMultiplier($campaign->format);
        $subscriberEarn   = config('oonclick.subscriber_earn', 60);
        $baseAmount       = (int) floor($subscriberEarn * $formatMultiplier);

        // Appliquer le multiplicateur de niveau si la feature 'levels' est activée
        $earningMultiplier = 1.0;
        if (FeatureSetting::isEnabled('levels')) {
            $levelConfig       = FeatureSetting::getConfig('levels');
            $subscriber        = $adView->subscriber;
            $userLevel         = $this->gamificationService->getUserLevel($subscriber);
            $levelData         = collect($levelConfig['levels'] ?? [])->firstWhere('level', $userLevel);
            $earningMultiplier = (float) ($levelData['multiplier'] ?? 1.0);
        }

        // Appliquer le multiplicateur de streak si la feature 'streak' est activée
        $streakMultiplier = 1.0;
        if (FeatureSetting::isEnabled('streak')) {
            $streakConfig     = FeatureSetting::getConfig('streak');
            $multiplierThreshold = $streakConfig['streak_multiplier_threshold'] ?? 7;
            $streakMultiplierValue = (float) ($streakConfig['streak_multiplier'] ?? 1.0);

            // Récupérer le streak courant de l'abonné (streak_day du dernier check-in)
            $subscriber       = $adView->subscriber ?? User::find($adView->subscriber_id);
            $lastCheckin      = \App\Models\DailyCheckin::where('user_id', $subscriber->id)
                ->latest('checked_in_at')
                ->first();
            $currentStreak    = $lastCheckin?->streak_day ?? 0;

            if ($currentStreak >= $multiplierThreshold) {
                $streakMultiplier = $streakMultiplierValue;
            }
        }

        $subscriberAmount = (int) floor($baseAmount * $earningMultiplier * $streakMultiplier);
        $costPerView      = $campaign->cost_per_view ?? config('oonclick.cost_per_view', 100);
        $platformFee      = $costPerView - $subscriberAmount;

        // S'assurer que les frais plateforme ne sont pas négatifs
        if ($platformFee < 0) {
            $platformFee = 0;
        }

        // 5. Transaction principale : crédit wallet + escrow + mise à jour des modèles
        // La variable $percent est capturée pour le metadata du job anti-fraude.
        DB::transaction(function () use ($adView, $campaign, $watchDurationSeconds, $subscriberAmount, $platformFee) {
            // Marquer la vue comme complétée et créditée
            $adView->is_completed          = true;
            $adView->completed_at          = now();
            $adView->watch_duration_seconds = $watchDurationSeconds;
            $adView->is_credited           = true;
            $adView->credited_at           = now();
            $adView->amount_credited       = $subscriberAmount;
            $adView->save();

            // Incrémenter views_count avec lockForUpdate pour éviter les race conditions
            Campaign::where('id', $campaign->id)
                ->lockForUpdate()
                ->firstOrFail()
                ->increment('views_count');

            // Libérer l'escrow (part abonné + frais plateforme)
            $this->escrowService->release($campaign->id, $subscriberAmount, $platformFee);

            // Créditer le wallet de l'abonné
            $this->walletService->credit(
                $adView->subscriber_id,
                $subscriberAmount,
                'credit',
                "Vue pub #{$campaign->id}",
                [
                    'campaign_id' => $campaign->id,
                    'ad_view_id'  => $adView->id,
                ]
            );

            // Mettre à jour CampaignTarget si existant
            CampaignTarget::where('campaign_id', $campaign->id)
                ->where('subscriber_id', $adView->subscriber_id)
                ->update(['status' => 'viewed']);

            // Recharger views_count pour vérifier si le quota est atteint
            $campaign->refresh();

            if ($campaign->views_count >= $campaign->max_views) {
                CompleteCampaignJob::dispatch($campaign->id);
            }
        });

        // Diffuser la progression en temps réel pour la page de détail annonceur
        // Hors transaction — une erreur de broadcast ne doit pas rollback le crédit
        try {
            event(new \App\Events\CampaignProgressUpdated($campaign->fresh()));
        } catch (\Throwable $e) {
            Log::warning("CampaignProgressUpdated broadcast failed for campaign #{$campaign->id}: {$e->getMessage()}");
        }

        // Attribuer les XP pour vue complétée — +10 XP par pub (US-050)
        // Hors transaction pour ne pas bloquer en cas d'erreur non critique
        $subscriber = $adView->subscriber;
        try {
            $this->gamificationService->awardXp($subscriber, 10, "Vue pub #{$campaign->id}");
        } catch (\Throwable $e) {
            Log::warning("GamificationService XP award failed for view #{$adView->id}: {$e->getMessage()}");
        }

        // Incrémenter la progression des missions de type 'views'
        try {
            app(MissionService::class)->incrementProgress($subscriber, 'views');
        } catch (\Throwable $e) {
            Log::warning("MissionService incrementProgress failed for view #{$adView->id}: {$e->getMessage()}");
        }

        // Notifier l'abonné de son crédit — hors transaction pour ne pas bloquer
        $newBalance = $this->walletService->getBalance($adView->subscriber_id);
        $adView->subscriber->notify(
            new \App\Notifications\CreditReceivedNotification($subscriberAmount, $adView->campaign_id, $newBalance)
        );

        // Collecter automatiquement un coupon lié à la campagne si la feature est activée
        try {
            if (FeatureSetting::isEnabled('coupons')) {
                $couponConfig = FeatureSetting::getConfig('coupons');
                if ($couponConfig['auto_collect_on_view'] ?? true) {
                    $coupon = Coupon::where('campaign_id', $campaign->id)
                        ->where('is_active', true)
                        ->first();
                    if ($coupon) {
                        $maxPerUser      = $couponConfig['max_coupons_per_user'] ?? 20;
                        $userCouponCount = UserCoupon::where('user_id', $adView->subscriber_id)->count();
                        if ($userCouponCount < $maxPerUser) {
                            UserCoupon::firstOrCreate(
                                ['user_id' => $adView->subscriber_id, 'coupon_id' => $coupon->id],
                                ['collected_at' => now()]
                            );
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Coupon auto-collect failed for view #{$adView->id}: {$e->getMessage()}");
        }

        // Dispatch asynchrone — ne bloque pas la réponse et est hors transaction
        // pour éviter qu'une erreur dans le job ne rollback le crédit.
        \App\Modules\Fraud\Jobs\FraudDetectionJob::dispatch(
            $adView->subscriber_id,
            'invalid_completion',
            'low',
            'Analyse post-vue',
            ['ad_view_id' => $adView->id, 'watch_percent' => $percent ?? 100]
        )->onQueue('fraud');

        return ['credited' => true, 'amount' => $subscriberAmount, 'reason' => null];
    }

    /**
     * Marque une vue comme complétée sans crédit (durée insuffisante).
     *
     * @param AdView $adView               Vue à marquer
     * @param int    $watchDurationSeconds Durée effective de visionnage
     */
    private function markViewIncomplete(AdView $adView, int $watchDurationSeconds): void
    {
        $adView->is_completed           = true;
        $adView->completed_at           = now();
        $adView->watch_duration_seconds = $watchDurationSeconds;
        $adView->is_credited            = false;
        $adView->save();
    }
}
