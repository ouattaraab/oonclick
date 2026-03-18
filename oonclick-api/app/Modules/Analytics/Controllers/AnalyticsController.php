<?php

namespace App\Modules\Analytics\Controllers;

use App\Models\Campaign;
use App\Modules\Analytics\Services\CampaignAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly CampaignAnalyticsService $analyticsService,
    ) {}

    /**
     * Retourne les statistiques détaillées d'une campagne pour le tableau
     * de bord annonceur (impressions, taux de complétion, dépenses...).
     */
    public function campaignStats(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        if (! $request->user()->isAdmin() && $campaign->advertiser_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $stats = $this->analyticsService->getStats($id);

        return response()->json($stats, 200);
    }

    /**
     * Génère et retourne un rapport PDF complet pour une campagne donnée.
     */
    public function exportPdf(Request $request, int $id): Response
    {
        $campaign = Campaign::findOrFail($id);

        if (! $request->user()->isAdmin() && $campaign->advertiser_id !== $request->user()->id) {
            abort(403, 'Accès non autorisé.');
        }

        $stats = $this->analyticsService->getStats($id);

        $pdf = Pdf::loadView('pdf.campaign-report', [
            'campaign' => $campaign,
            'stats'    => $stats,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("rapport-campagne-{$id}.pdf");
    }
}
