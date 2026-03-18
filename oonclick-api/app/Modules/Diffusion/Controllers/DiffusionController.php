<?php

namespace App\Modules\Diffusion\Controllers;

use App\Models\AdView;
use App\Models\Campaign;
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

            return [
                'id'                => $campaign->id,
                'title'             => $campaign->title,
                'format'            => $campaign->format,
                'media_url'         => $presignedUrl,
                'thumbnail_url'     => $campaign->thumbnail_url,
                'duration_seconds'  => $campaign->duration_seconds,
                'amount'            => $amount,
                'format_multiplier' => $multiplier,
            ];
        });

        return response()->json(['data' => $data]);
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
}
