<?php

namespace App\Modules\Payment\Controllers;

use App\Modules\Payment\Services\PaystackService;
use App\Modules\Campaign\Jobs\ProcessCampaignPaymentJob;
use App\Modules\Payment\Jobs\ProcessWithdrawalJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private readonly PaystackService $paystackService,
    ) {}

    /**
     * Reçoit et traite les événements webhook envoyés par Paystack.
     *
     * La signature HMAC-SHA512 est vérifiée en premier. Toute requête avec
     * une signature invalide est rejetée avec un 401. Un code 200 est
     * toujours retourné immédiatement après le dispatch du job afin de
     * ne pas dépasser le timeout de Paystack.
     *
     * Événements gérés :
     *   - charge.success  → ProcessCampaignPaymentJob
     *   - transfer.success / transfer.failed → ProcessWithdrawalJob
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Vérifier la signature HMAC avant tout traitement
        $signature = $request->header('x-paystack-signature', '');

        if (! $this->paystackService->verifyWebhookSignature($request->getContent(), $signature)) {
            Log::warning('Paystack webhook : signature invalide', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Signature invalide'], 401);
        }

        // 2. Parser l'événement
        $event = $request->input('event');
        $data  = $request->input('data', []);

        Log::info('Paystack webhook reçu', ['event' => $event]);

        // 3. Router selon l'événement
        switch ($event) {
            case 'charge.success':
                $reference  = $data['reference'] ?? null;
                $campaignId = $data['metadata']['campaign_id'] ?? null;

                if ($reference && $campaignId) {
                    ProcessCampaignPaymentJob::dispatch($reference, (int) $campaignId);
                } else {
                    Log::warning('Paystack charge.success : données manquantes', ['data' => $data]);
                }
                break;

            case 'transfer.success':
                $withdrawalId = $data['reason_data']['withdrawal_id']
                    ?? $data['metadata']['withdrawal_id']
                    ?? null;

                if ($withdrawalId) {
                    // Notifier le job que le transfert est confirmé
                    // Le job vérifie le statut et met à jour le Withdrawal
                    ProcessWithdrawalJob::dispatch((int) $withdrawalId)
                        ->onQueue('default');
                } else {
                    Log::warning('Paystack transfer.success : withdrawal_id manquant', ['data' => $data]);
                }

                $withdrawal = \App\Models\Withdrawal::where('paystack_transfer_code', $data['transfer_code'] ?? '')->first();
                if ($withdrawal) {
                    $withdrawal->user->notify(new \App\Notifications\WithdrawalStatusNotification($withdrawal));
                }
                break;

            case 'transfer.failed':
                $withdrawalId = $data['reason_data']['withdrawal_id']
                    ?? $data['metadata']['withdrawal_id']
                    ?? null;

                if ($withdrawalId) {
                    ProcessWithdrawalJob::dispatch((int) $withdrawalId)
                        ->onQueue('default');
                } else {
                    Log::warning('Paystack transfer.failed : withdrawal_id manquant', ['data' => $data]);
                }

                $withdrawal = \App\Models\Withdrawal::where('paystack_transfer_code', $data['transfer_code'] ?? '')->first();
                if ($withdrawal) {
                    $withdrawal->user->notify(new \App\Notifications\WithdrawalStatusNotification($withdrawal));
                }
                break;

            default:
                Log::info('Paystack webhook : événement non géré', ['event' => $event]);
                break;
        }

        // 4. Toujours retourner 200 immédiatement (Paystack attend une réponse rapide)
        return response()->json(['message' => 'OK'], 200);
    }
}
