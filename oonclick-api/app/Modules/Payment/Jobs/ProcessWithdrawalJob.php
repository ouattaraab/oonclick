<?php

namespace App\Modules\Payment\Jobs;

use App\Models\Withdrawal;
use App\Modules\Payment\Services\PaystackService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessWithdrawalJob implements ShouldQueue
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
     * @param int $withdrawalId Identifiant de la demande de retrait
     */
    public function __construct(
        public readonly int $withdrawalId,
    ) {}

    /**
     * Exécute le job de traitement du retrait mobile money.
     *
     * Étapes :
     *   1. Récupérer le Withdrawal et verrouiller la ligne
     *   2. Vérifier l'idempotence (statut = pending uniquement)
     *   3. Créer le bénéficiaire Paystack si inexistant
     *   4. Initier le transfert Paystack
     *   5. Mettre à jour le statut à 'processing'
     *
     * @param PaystackService $paystackService
     * @return void
     */
    public function handle(PaystackService $paystackService): void
    {
        Log::info('ProcessWithdrawalJob : démarrage', [
            'withdrawal_id' => $this->withdrawalId,
        ]);

        DB::transaction(function () use ($paystackService) {
            /** @var Withdrawal $withdrawal */
            $withdrawal = Withdrawal::lockForUpdate()->findOrFail($this->withdrawalId);

            // 2. Idempotence : on ne traite que les retraits en attente
            if ($withdrawal->status !== 'pending') {
                Log::info('ProcessWithdrawalJob : retrait déjà traité, ignoré', [
                    'withdrawal_id' => $this->withdrawalId,
                    'status'        => $withdrawal->status,
                ]);

                return;
            }

            // 3. Créer le bénéficiaire Paystack si le transfer_code est absent
            $recipientCode = $withdrawal->paystack_transfer_code;

            if (! $recipientCode) {
                $operatorCode = $paystackService->getMobileOperatorCode(
                    $withdrawal->mobile_operator
                );

                // Récupérer le nom du bénéficiaire depuis le wallet → user
                $withdrawal->loadMissing('user');
                $recipientName = $withdrawal->user->name ?? 'Abonné oon.click';

                $recipientCode = $paystackService->createTransferRecipient(
                    $recipientName,
                    $withdrawal->mobile_phone,
                    $operatorCode
                );
            }

            // 4. Générer une référence unique si absente
            $paystackRef = $withdrawal->paystack_reference
                ?? 'WD_' . Str::upper(Str::random(16));

            // Initier le transfert Paystack (montant net après frais)
            $transferResult = $paystackService->initializeTransfer(
                $withdrawal->net_amount,
                $recipientCode,
                $paystackRef
            );

            // 5. Mettre à jour le Withdrawal
            $withdrawal->paystack_transfer_code = $transferResult['transfer_code'];
            $withdrawal->paystack_reference     = $paystackRef;
            $withdrawal->status                 = 'processing';
            $withdrawal->save();

            Log::info('ProcessWithdrawalJob : transfert initié', [
                'withdrawal_id'  => $this->withdrawalId,
                'transfer_code'  => $transferResult['transfer_code'],
                'paystack_ref'   => $paystackRef,
                'transfer_status' => $transferResult['status'],
            ]);
        });
    }

    /**
     * Gère l'échec définitif du job après épuisement des tentatives.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessWithdrawalJob : échec définitif', [
            'withdrawal_id' => $this->withdrawalId,
            'error'         => $exception->getMessage(),
        ]);

        // Marquer le retrait comme échoué
        Withdrawal::where('id', $this->withdrawalId)
            ->where('status', 'pending')
            ->update([
                'status'         => 'failed',
                'failure_reason' => 'Échec du transfert Paystack après plusieurs tentatives.',
            ]);
    }
}
