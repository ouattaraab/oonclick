<?php

namespace App\Modules\Campaign\Controllers;

use App\Models\Campaign;
use App\Modules\Campaign\Requests\StoreCampaignRequest;
use App\Modules\Campaign\Requests\UpdateCampaignRequest;
use App\Modules\Campaign\Requests\UploadMediaRequest;
use App\Modules\Campaign\Services\CampaignService;
use App\Modules\Campaign\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService,
        private readonly MediaService $mediaService,
    ) {}

    /**
     * Crée une nouvelle campagne publicitaire (annonceur uniquement).
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAdvertiser()) {
            return response()->json(['message' => 'Accès réservé aux annonceurs.'], 403);
        }

        $campaign = $this->campaignService->create($request->validated(), $user->id);

        return response()->json([
            'message'  => 'Campagne créée avec succès.',
            'campaign' => $campaign,
        ], 201);
    }

    /**
     * Liste les campagnes de l'annonceur authentifié (admin : toutes les campagnes).
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Campaign::query();

        if (! $user->isAdmin()) {
            $query->where('advertiser_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('format')) {
            $query->where('format', $request->query('format'));
        }

        $paginator = $query->latest()->paginate(15);

        return response()->json([
            'data'         => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'total_pages'  => $paginator->lastPage(),
            'total'        => $paginator->total(),
        ]);
    }

    /**
     * Affiche le détail d'une campagne avec statistiques et escrow.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $campaign = Campaign::with('escrow')->findOrFail($id);

        if (! $user->isAdmin() && $campaign->advertiser_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé à cette campagne.'], 403);
        }

        return response()->json([
            'campaign'        => $campaign,
            'views_count'     => $campaign->views_count,
            'budget_used'     => $campaign->budget_used,
            'remaining_views' => $campaign->remaining_views,
        ]);
    }

    /**
     * Met à jour les informations d'une campagne (statut draft uniquement).
     */
    public function update(UpdateCampaignRequest $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $campaign = Campaign::findOrFail($id);

        if (! $user->isAdmin() && $campaign->advertiser_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé à cette campagne.'], 403);
        }

        $data = $request->validated();

        $campaign->fill($data);

        // Recalculer max_views si budget ou cost_per_view change
        if (isset($data['budget']) || isset($data['cost_per_view'])) {
            $budget      = $campaign->budget;
            $costPerView = $campaign->cost_per_view;
            $campaign->max_views = (int) floor($budget / $costPerView);
        }

        $campaign->save();

        return response()->json([
            'message'  => 'Campagne mise à jour.',
            'campaign' => $campaign->fresh(),
        ]);
    }

    /**
     * Supprime une campagne (statut draft uniquement, soft delete).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $campaign = Campaign::findOrFail($id);

        if (! $user->isAdmin() && $campaign->advertiser_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé à cette campagne.'], 403);
        }

        if ($campaign->status !== 'draft') {
            return response()->json(['message' => 'Seules les campagnes en brouillon peuvent être supprimées.'], 422);
        }

        $campaign->delete();

        return response()->json(['message' => 'Campagne supprimée.']);
    }

    /**
     * Uploade le média principal et/ou la miniature d'une campagne.
     */
    public function uploadMedia(UploadMediaRequest $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $campaign = Campaign::findOrFail($id);

        if (! $user->isAdmin() && $campaign->advertiser_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé à cette campagne.'], 403);
        }

        if ($campaign->status !== 'draft') {
            return response()->json(['message' => 'Le média ne peut être modifié que sur une campagne en brouillon.'], 422);
        }

        if ($request->hasFile('media')) {
            $mediaFile = $request->file('media');
            $this->campaignService->uploadMedia($campaign, $mediaFile, 'media');

            // Tenter d'extraire la durée via ffprobe (optionnel)
            if (in_array($mediaFile->getClientOriginalExtension(), ['mp4', 'mov', 'avi', 'webm'])) {
                $this->tryExtractDuration($campaign, $mediaFile->getRealPath());
            }
        }

        if ($request->hasFile('thumbnail')) {
            $this->campaignService->uploadMedia($campaign, $request->file('thumbnail'), 'thumbnail');
        }

        return response()->json([
            'message'  => 'Média uploadé avec succès.',
            'campaign' => $campaign->fresh(),
        ]);
    }

    /**
     * Soumet la campagne pour review par l'équipe modération.
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $campaign = Campaign::findOrFail($id);

        if (! $user->isAdmin() && $campaign->advertiser_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé à cette campagne.'], 403);
        }

        $campaign = $this->campaignService->submit($campaign);

        return response()->json([
            'message'  => 'Campagne soumise pour validation.',
            'campaign' => $campaign,
        ]);
    }

    /**
     * Met en pause une campagne active.
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $campaign = Campaign::findOrFail($id);

        if (! $user->isAdmin() && $campaign->advertiser_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé à cette campagne.'], 403);
        }

        $campaign = $this->campaignService->pause($campaign);

        return response()->json([
            'message'  => 'Campagne mise en pause.',
            'campaign' => $campaign,
        ]);
    }

    /**
     * Reprend une campagne mise en pause.
     */
    public function resume(Request $request, int $id): JsonResponse
    {
        $user     = $request->user();
        $campaign = Campaign::findOrFail($id);

        if (! $user->isAdmin() && $campaign->advertiser_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé à cette campagne.'], 403);
        }

        $campaign = $this->campaignService->resume($campaign);

        return response()->json([
            'message'  => 'Campagne reprise.',
            'campaign' => $campaign,
        ]);
    }

    /**
     * Tente d'extraire la durée d'une vidéo via ffprobe et met à jour la campagne.
     * L'opération est silencieuse si ffprobe est absent ou échoue.
     */
    private function tryExtractDuration(Campaign $campaign, string $filePath): void
    {
        $ffprobe = trim(shell_exec('which ffprobe 2>/dev/null') ?? '');

        if (empty($ffprobe)) {
            return;
        }

        $output = shell_exec(
            sprintf(
                '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>/dev/null',
                escapeshellcmd($ffprobe),
                escapeshellarg($filePath)
            )
        );

        $duration = $output ? (int) round((float) trim($output)) : null;

        if ($duration && $duration > 0) {
            $campaign->duration_seconds = $duration;
            $campaign->save();
        }
    }
}
