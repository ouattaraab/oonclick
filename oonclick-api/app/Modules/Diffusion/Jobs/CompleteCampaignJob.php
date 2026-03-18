<?php

namespace App\Modules\Diffusion\Jobs;

use App\Models\Campaign;
use App\Modules\Campaign\Services\CampaignService;
use App\Modules\Payment\Services\EscrowService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CompleteCampaignJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives maximum avant abandon.
     */
    public int $tries = 3;

    /**
     * Délais entre les tentatives en secondes : 60 s, 5 min, 15 min.
     */
    public array $backoff = [60, 300, 900];

    /**
     * Nom de la queue cible.
     */

    /**
     * @param int $campaignId Identifiant de la campagne à compléter
     */
    public function __construct(
        public readonly int $campaignId,
    ) {}

    /**
     * Complète une campagne dont le quota de vues est atteint.
     *
     * Étapes :
     *   1. Récupérer la campagne et vérifier qu'elle est encore active
     *   2. Vérifier que views_count >= max_views (idempotence)
     *   3. Déléguer la complétion à CampaignService::complete()
     *   4. Vérifier le budget restant en escrow pour signalement éventuel
     *
     * Le remboursement automatique du budget restant via Paystack est prévu
     * en V2. En attendant, tout solde résiduel est loggué pour traitement
     * manuel par l'équipe financière.
     *
     * @param CampaignService $campaignService
     * @param EscrowService   $escrowService
     */
    public function handle(CampaignService $campaignService, EscrowService $escrowService): void
    {
        Log::info('CompleteCampaignJob : démarrage', [
            'campaign_id' => $this->campaignId,
        ]);

        /** @var Campaign|null $campaign */
        $campaign = Campaign::find($this->campaignId);

        // 1. Vérifier que la campagne existe et est encore active
        if ($campaign === null) {
            Log::warning('CompleteCampaignJob : campagne introuvable', [
                'campaign_id' => $this->campaignId,
            ]);

            return;
        }

        if ($campaign->status !== 'active') {
            Log::info('CompleteCampaignJob : campagne déjà non-active, ignoré', [
                'campaign_id' => $this->campaignId,
                'status'      => $campaign->status,
            ]);

            return;
        }

        // 2. Vérifier le quota (idempotence)
        if ($campaign->views_count < $campaign->max_views) {
            Log::info('CompleteCampaignJob : quota non encore atteint, ignoré', [
                'campaign_id' => $this->campaignId,
                'views_count' => $campaign->views_count,
                'max_views'   => $campaign->max_views,
            ]);

            return;
        }

        // 3. Compléter la campagne via le service métier
        $campaignService->complete($campaign);

        Log::info('CompleteCampaignJob : campagne complétée', [
            'campaign_id' => $this->campaignId,
            'views_count' => $campaign->views_count,
            'max_views'   => $campaign->max_views,
        ]);

        // 4. Vérifier le budget restant en escrow
        try {
            $remainingBalance = $escrowService->getRemainingBalance($this->campaignId);

            if ($remainingBalance > 0) {
                // V2 : remboursement automatique via Paystack
                // En attendant, logguer pour remboursement manuel
                Log::warning('CompleteCampaignJob : budget escrow résiduel — remboursement manuel requis', [
                    'campaign_id'      => $this->campaignId,
                    'remaining_amount' => $remainingBalance,
                    'action_required'  => 'Remboursement annonceur à traiter manuellement (Paystack V2)',
                ]);
            }
        } catch (\Throwable $e) {
            // L'escrow peut ne plus exister si déjà libéré totalement
            Log::info('CompleteCampaignJob : impossible de vérifier le solde escrow', [
                'campaign_id' => $this->campaignId,
                'reason'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Gère l'échec définitif du job après épuisement des tentatives.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CompleteCampaignJob : échec définitif', [
            'campaign_id' => $this->campaignId,
            'error'       => $exception->getMessage(),
        ]);
    }
}
