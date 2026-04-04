<?php

namespace App\Modules\Diffusion\Controllers;

use App\Models\AdView;
use App\Models\Campaign;
use App\Models\FeatureSetting;
use App\Modules\Campaign\Services\MediaService;
use App\Modules\Diffusion\Services\MatchingService;
use App\Modules\Diffusion\Services\ViewTrackingService;
use App\Modules\Payment\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class DiffusionController extends Controller
{
    public function __construct(
        private readonly MatchingService     $matchingService,
        private readonly ViewTrackingService $viewTrackingService,
        private readonly MediaService        $mediaService,
        private readonly WalletService       $walletService,
    ) {}

    /**
     * Retourne la liste des publicités disponibles pour l'abonné authentifié.
     *
     * Le feed est filtré selon le profil de l'abonné (ville, genre, âge,
     * opérateur, intérêts) et limité à 10 campagnes triées par priorité
     * de diffusion (les moins vues en premier).
     *
     * Chaque campagne expose une URL média signée valable 15 minutes pour
     * une lecture sécurisée sans exposer la clé R2 au client.
     * Les URLs signées ne sont jamais loguées.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'subscriber') {
            return response()->json(['message' => 'Accès réservé aux abonnés.'], 403);
        }

        $campaigns = $this->matchingService->getEligibleCampaigns($user);

        $data = $campaigns->map(function (Campaign $campaign) {
            $multiplier = config('oonclick.format_multipliers')[$campaign->format] ?? 1.0;
            $amount     = (int) floor(config('oonclick.subscriber_earn', 60) * $multiplier);

            // Générer l'URL présignée (15 min) — ne jamais logger cette URL
            $presignedUrl = null;
            if ($campaign->media_path) {
                try {
                    $presignedUrl = $this->mediaService->generatePresignedUrl($campaign->media_path, 15);
                } catch (\Throwable $e) {
                    // Fallback : URL CDN publique si la génération de presigned échoue
                    $presignedUrl = $campaign->media_url;

                    Log::warning('Diffusion : impossible de générer la presigned URL', [
                        'campaign_id' => $campaign->id,
                        'error'       => $e->getMessage(),
                    ]);
                }
            } else {
                $presignedUrl = $campaign->media_url;
            }

            $result = [
                'id'                => $campaign->id,
                'title'             => $campaign->title,
                'format'            => $campaign->format,
                'media_url'         => $presignedUrl,
                'thumbnail_url'     => $campaign->thumbnail_url,
                'duration_seconds'  => $campaign->duration_seconds,
                'amount'            => $amount,
                'format_multiplier' => $multiplier,
            ];

            // Include quiz data for quiz format campaigns
            if ($campaign->format === 'quiz' && $campaign->quiz_data) {
                $quizData = $campaign->quiz_data;
                $result['quiz_data'] = is_string($quizData) ? json_decode($quizData, true) : $quizData;
            }

            return $result;
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Retourne l'historique des publicités regardées par l'abonné.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'subscriber') {
            return response()->json(['message' => 'Accès réservé aux abonnés.'], 403);
        }

        $views = AdView::where('subscriber_id', $user->id)
            ->where('is_completed', true)
            ->with('campaign')
            ->orderByDesc('completed_at')
            ->paginate(20);

        $data = $views->getCollection()->map(function (AdView $view) {
            $campaign = $view->campaign;
            if (!$campaign) return null;

            $multiplier = config('oonclick.format_multipliers')[$campaign->format] ?? 1.0;
            $amount     = (int) floor(config('oonclick.subscriber_earn', 60) * $multiplier);

            $mediaUrl = $campaign->media_url;
            if ($campaign->media_path) {
                try {
                    $mediaUrl = $this->mediaService->generatePresignedUrl($campaign->media_path, 15);
                } catch (\Throwable $e) {
                    $mediaUrl = $campaign->media_url;
                }
            }

            return [
                'id'                => $campaign->id,
                'title'             => $campaign->title,
                'format'            => $campaign->format,
                'media_url'         => $mediaUrl,
                'thumbnail_url'     => $campaign->thumbnail_url,
                'duration_seconds'  => $campaign->duration_seconds,
                'amount'            => $amount,
                'format_multiplier' => $multiplier,
                'viewed_at'         => $view->completed_at?->toIso8601String(),
                'amount_credited'   => $view->amount_credited,
                'total_views'       => $campaign->views_count,
            ];
        })->filter()->values();

        return response()->json([
            'data'        => $data,
            'current_page' => $views->currentPage(),
            'last_page'    => $views->lastPage(),
            'total'        => $views->total(),
        ]);
    }

    /**
     * Démarre la session de visualisation d'une publicité.
     *
     * Vérifie les conditions d'éligibilité (compte actif, profil complété,
     * trust score, limites anti-fraude, campagne disponible) avant d'enregistrer
     * le début de la vue et de retourner les informations nécessaires au player.
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'subscriber') {
            return response()->json(['message' => 'Accès réservé aux abonnés.'], 403);
        }

        $campaign = Campaign::find($id);

        if ($campaign === null) {
            return response()->json(['message' => 'Campagne introuvable.'], 404);
        }

        $check = $this->viewTrackingService->canView($user, $campaign);

        if (! $check['allowed']) {
            return response()->json([
                'message' => $check['reason'],
            ], 403);
        }

        $deviceData = $request->only(['device_fingerprint', 'platform', 'device_model']);

        $adView = $this->viewTrackingService->startView($user, $campaign, $deviceData);

        // expires_at = heure de début + durée de la campagne + 30 secondes de buffer
        $durationSeconds = $campaign->duration_seconds ?? 0;
        $expiresAt       = $adView->started_at->copy()->addSeconds($durationSeconds + 30);

        return response()->json([
            'ad_view_id' => $adView->id,
            'started_at' => $adView->started_at->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
        ], 201);
    }

    /**
     * Marque une publicité comme complètement visionnée.
     *
     * Valide la durée de visionnage par rapport au seuil minimum configuré
     * (80% par défaut), puis crédite le wallet de l'abonné si éligible.
     * La réponse inclut toujours le nouveau solde pour mettre à jour l'UI
     * Flutter sans nécessiter un appel supplémentaire au wallet.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'subscriber') {
            return response()->json(['message' => 'Accès réservé aux abonnés.'], 403);
        }

        $validated = $request->validate([
            'ad_view_id'            => ['required', 'integer'],
            'watch_duration_seconds' => ['required', 'integer', 'min:0'],
        ]);

        $adView = AdView::find($validated['ad_view_id']);

        if ($adView === null) {
            return response()->json(['message' => 'Session de visionnage introuvable.'], 404);
        }

        // Vérifier que la vue appartient bien à cet abonné et à cette campagne
        if ($adView->subscriber_id !== $user->id || $adView->campaign_id !== $id) {
            return response()->json(['message' => 'Accès non autorisé à cette session.'], 403);
        }

        $result = $this->viewTrackingService->completeView(
            $adView,
            $validated['watch_duration_seconds']
        );

        if (! $result['credited']) {
            return response()->json([
                'credited' => false,
                'reason'   => $result['reason'],
            ]);
        }

        $newBalance = $this->walletService->getBalance($user->id);

        return response()->json([
            'credited'    => true,
            'amount'      => $result['amount'],
            'new_balance' => $newBalance,
            'message'     => "{$result['amount']} FCFA crédités sur votre portefeuille",
        ]);
    }

    /**
     * Pré-charge les campagnes disponibles pour un visionnage hors-ligne.
     *
     * Retourne une liste de campagnes avec leurs URLs CDN et une durée de
     * validité configurée. Disponible uniquement si la feature 'offline_mode'
     * est activée.
     */
    public function preload(Request $request): JsonResponse
    {
        if (! FeatureSetting::isEnabled('offline_mode')) {
            return response()->json(['message' => 'Mode hors-ligne désactivé.'], 403);
        }

        $config       = FeatureSetting::getConfig('offline_mode');
        $maxCampaigns = $config['max_preload_campaigns'] ?? 5;
        $validityHours = $config['preload_validity_hours'] ?? 24;

        $user      = $request->user();
        $campaigns = $this->matchingService->getEligibleCampaigns($user);

        $preloadable = $campaigns->take($maxCampaigns)->map(function (Campaign $campaign) use ($validityHours) {
            $multiplier = config('oonclick.format_multipliers')[$campaign->format] ?? 1.0;
            $amount     = (int) floor(config('oonclick.subscriber_earn', 60) * $multiplier);

            // Utiliser l'URL CDN publique pour le hors-ligne (pas de presigned URL
            // courte durée — la validité est gérée côté client via valid_until)
            $mediaUrl = $campaign->media_url;
            if ($campaign->media_path) {
                try {
                    // URL presignée avec longue durée (validityHours × 60 minutes)
                    $mediaUrl = $this->mediaService->generatePresignedUrl(
                        $campaign->media_path,
                        $validityHours * 60
                    );
                } catch (\Throwable $e) {
                    $mediaUrl = $campaign->media_url;
                    Log::warning('Preload : impossible de générer la presigned URL', [
                        'campaign_id' => $campaign->id,
                        'error'       => $e->getMessage(),
                    ]);
                }
            }

            return [
                'id'               => $campaign->id,
                'title'            => $campaign->title,
                'format'           => $campaign->format,
                'media_url'        => $mediaUrl,
                'thumbnail_url'    => $campaign->thumbnail_url,
                'duration_seconds' => $campaign->duration_seconds,
                'amount'           => $amount,
                'valid_until'      => now()->addHours($validityHours)->toISOString(),
            ];
        });

        return response()->json([
            'campaigns'    => $preloadable,
            'preloaded_at' => now()->toISOString(),
            'valid_hours'  => $validityHours,
        ]);
    }

    /**
     * Synchronise les visionnages effectués hors-ligne avec le serveur.
     *
     * Chaque entrée de `completions` est traitée comme un visionnage complet :
     * démarrage + complétion via ViewTrackingService. Les erreurs individuelles
     * sont rapportées sans interrompre le traitement du lot.
     */
    public function sync(Request $request): JsonResponse
    {
        if (! FeatureSetting::isEnabled('offline_mode')) {
            return response()->json(['message' => 'Mode hors-ligne désactivé.'], 403);
        }

        $config      = FeatureSetting::getConfig('offline_mode');
        $maxBatch    = $config['sync_max_batch_size'] ?? 10;

        $data = $request->validate([
            'completions'                           => 'required|array|max:' . $maxBatch,
            'completions.*.campaign_id'             => 'required|integer|exists:campaigns,id',
            'completions.*.watch_duration_seconds'  => 'required|integer|min:1',
            'completions.*.completed_at'            => 'required|date',
        ]);

        $user    = $request->user();
        $results = [];

        foreach ($data['completions'] as $completion) {
            try {
                $campaign = Campaign::findOrFail($completion['campaign_id']);

                $adView = $this->viewTrackingService->startView($user, $campaign, []);

                $result = $this->viewTrackingService->completeView(
                    $adView,
                    (int) $completion['watch_duration_seconds']
                );

                $results[] = [
                    'campaign_id' => $completion['campaign_id'],
                    'success'     => true,
                    'credited'    => $result['credited'] ?? false,
                    'amount'      => $result['amount'] ?? 0,
                ];
            } catch (\Throwable $e) {
                Log::warning('Offline sync : erreur de traitement', [
                    'campaign_id' => $completion['campaign_id'],
                    'user_id'     => $user->id,
                    'error'       => $e->getMessage(),
                ]);

                $results[] = [
                    'campaign_id' => $completion['campaign_id'],
                    'success'     => false,
                    'error'       => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'results'      => $results,
            'synced_count' => count($results),
        ]);
    }
}
