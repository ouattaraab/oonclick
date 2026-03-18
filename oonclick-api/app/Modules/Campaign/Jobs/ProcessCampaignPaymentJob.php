<?php

namespace App\Modules\Campaign\Jobs;

use App\Models\Campaign;
use App\Modules\Campaign\Events\CampaignApproved;
use App\Modules\Payment\Services\EscrowService;
use App\Modules\Payment\Services\PaystackService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCampaignPaymentJob implements ShouldQueue
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
     * Vérifie le paiement Paystack, bloque les fonds en escrow et
     * passe la campagne au statut 'approved'.
     *
     * @param string $paystackReference Référence de la transaction Paystack
     * @param int    $campaignId        Identifiant de la campagne concernée
     */
    public function __construct(
        public readonly string $paystackReference,
        public readonly int $campaignId,
    ) {}

    /**
     * Exécute le job.
     *
     * Étapes :
     *   1. Vérifier la transaction via Paystack API
     *   2. S'assurer que le statut est 'success'
     *   3. Convertir le montant de kobo en FCFA
     *   4. Bloquer les fonds en escrow
     *   5. Passer la campagne en statut 'approved'
     *   6. Dispatcher l'événement CampaignApproved
     *
     * @param PaystackService $paystackService
     * @param EscrowService   $escrowService
     * @return void
     */
    public function handle(
        PaystackService $paystackService,
        EscrowService $escrowService
    ): void {
        Log::info('ProcessCampaignPaymentJob : démarrage', [
            'campaign_id' => $this->campaignId,
            'reference'   => $this->paystackReference,
        ]);

        // 1. Vérifier la transaction Paystack
        $data = $paystackService->verifyTransaction($this->paystackReference);

        // 2. Vérifier que le paiement est bien confirmé
        if (($data['status'] ?? null) !== 'success') {
            Log::warning('ProcessCampaignPaymentJob : paiement non confirmé', [
                'campaign_id' => $this->campaignId,
                'reference'   => $this->paystackReference,
                'status'      => $data['status'] ?? 'inconnu',
            ]);

            // On ne relance pas automatiquement : le paiement n'est pas success
            $this->fail(
                new \Exception(
                    "Paiement Paystack non confirmé pour la référence {$this->paystackReference} "
                    . "(statut : " . ($data['status'] ?? 'inconnu') . ")."
                )
            );

            return;
        }

        // 3. Convertir kobo → FCFA (division par 100)
        $amountFcfa = (int) ($data['amount'] / 100);

        // 4. Bloquer les fonds en escrow
        $escrowService->lock($this->campaignId, $amountFcfa, $this->paystackReference);

        // 5. Passer la campagne en statut 'approved'
        $campaign = Campaign::findOrFail($this->campaignId);
        $campaign->status      = 'approved';
        $campaign->approved_at = now();
        $campaign->save();

        // 6. Dispatcher l'événement CampaignApproved
        CampaignApproved::dispatch($campaign);

        Log::info('ProcessCampaignPaymentJob : succès', [
            'campaign_id'  => $this->campaignId,
            'reference'    => $this->paystackReference,
            'amount_fcfa'  => $amountFcfa,
        ]);
    }
}
