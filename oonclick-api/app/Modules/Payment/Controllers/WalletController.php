<?php

namespace App\Modules\Payment\Controllers;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Withdrawal;
use App\Modules\Payment\Jobs\ProcessWithdrawalJob;
use App\Modules\Payment\Services\PaystackService;
use App\Modules\Payment\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly PaystackService $paystackService,
    ) {}

    /**
     * Retourne le wallet de l'utilisateur authentifié avec ses 5 dernières
     * transactions.
     */
    public function show(Request $request): JsonResponse
    {
        $user   = $request->user();
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (! $wallet) {
            return response()->json([
                'message' => 'Wallet introuvable.',
            ], 404);
        }

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'type', 'amount', 'balance_after', 'reference', 'description', 'status', 'created_at']);

        return response()->json([
            'wallet' => [
                'id'               => $wallet->id,
                'balance'          => $wallet->balance,
                'pending_balance'  => $wallet->pending_balance,
                'total_earned'     => $wallet->total_earned,
                'total_withdrawn'  => $wallet->total_withdrawn,
            ],
            'recent_transactions' => $transactions,
        ]);
    }

    /**
     * Retourne l'historique paginé des transactions du wallet (20 par page).
     *
     * Filtres disponibles via query string :
     *   - status : pending | completed | failed | cancelled
     *   - type   : credit | debit | pending | refund | bonus
     */
    public function transactions(Request $request): JsonResponse
    {
        $user   = $request->user();
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (! $wallet) {
            return response()->json([
                'message' => 'Wallet introuvable.',
            ], 404);
        }

        $query = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $transactions = $query->paginate(20, [
            'id', 'type', 'amount', 'balance_after', 'reference',
            'description', 'metadata', 'status', 'created_at',
        ]);

        return response()->json($transactions);
    }

    /**
     * Initie une demande de retrait vers le compte mobile money de l'utilisateur.
     *
     * Validations :
     *   - Montant entier >= min_withdrawal (config)
     *   - Opérateur parmi mtn, moov, orange
     *   - Numéro de téléphone au format E.164
     *   - kyc_level >= 1
     *   - Plafond de retrait selon kyc_level
     *   - Solde suffisant
     */
    public function withdraw(Request $request): JsonResponse
    {
        $minWithdrawal = (int) config('oonclick.min_withdrawal', 5000);

        $validated = $request->validate([
            'amount'          => ['required', 'integer', "min:{$minWithdrawal}"],
            'mobile_operator' => ['required', Rule::in(['mtn', 'moov', 'orange'])],
            'mobile_phone'    => ['required', 'string', 'regex:/^\+?[1-9]\d{7,14}$/'],
        ]);

        $user   = $request->user();
        $amount = (int) $validated['amount'];

        // Vérifier le KYC
        if ($user->kyc_level < 1) {
            return response()->json([
                'message' => 'Votre compte doit être vérifié (KYC niveau 1) pour effectuer un retrait.',
            ], 403);
        }

        // Vérifier le plafond de retrait selon le niveau KYC
        $maxWithdrawal = match (true) {
            $user->kyc_level >= 3 => (int) config('oonclick.kyc_level3_max_withdrawal', 1000000),
            $user->kyc_level >= 2 => (int) config('oonclick.kyc_level2_max_withdrawal', 100000),
            default               => (int) config('oonclick.kyc_level1_max_withdrawal', 10000),
        };

        if ($amount > $maxWithdrawal) {
            return response()->json([
                'message' => "Plafond de retrait dépassé pour votre niveau KYC. Maximum autorisé : {$maxWithdrawal} FCFA.",
            ], 422);
        }

        // Récupérer le wallet et vérifier le solde
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (! $wallet) {
            return response()->json([
                'message' => 'Wallet introuvable.',
            ], 404);
        }

        if ($wallet->balance < $amount) {
            return response()->json([
                'message' => "Solde insuffisant. Solde disponible : {$wallet->balance} FCFA.",
            ], 422);
        }

        $fee       = (int) config('oonclick.withdrawal_fee', 0);
        $netAmount = $amount - $fee;

        // Débiter le wallet avant de créer le retrait (atomique)
        try {
            $transaction = $this->walletService->debit(
                userId: $user->id,
                amount: $amount,
                type: 'debit',
                description: 'Retrait mobile money',
                metadata: ['mobile_operator' => $validated['mobile_operator']],
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        // Créer l'enregistrement de retrait
        $withdrawal = Withdrawal::create([
            'wallet_id'       => $wallet->id,
            'user_id'         => $user->id,
            'amount'          => $amount,
            'fee'             => $fee,
            'net_amount'      => $netAmount,
            'mobile_operator' => $validated['mobile_operator'],
            'mobile_phone'    => $validated['mobile_phone'],
            'status'          => 'pending',
        ]);

        // Dispatcher le job de traitement du retrait en queue
        ProcessWithdrawalJob::dispatch($withdrawal->id)->onQueue('default');

        Log::info('Retrait initié', [
            'user_id'       => $user->id,
            'withdrawal_id' => $withdrawal->id,
            'amount'        => $amount,
            'operator'      => $validated['mobile_operator'],
        ]);

        return response()->json([
            'message'    => 'Votre demande de retrait a été soumise.',
            'withdrawal' => [
                'id'              => $withdrawal->id,
                'amount'          => $withdrawal->amount,
                'fee'             => $withdrawal->fee,
                'net_amount'      => $withdrawal->net_amount,
                'mobile_operator' => $withdrawal->mobile_operator,
                'status'          => $withdrawal->status,
                'created_at'      => $withdrawal->created_at,
            ],
        ], 201);
    }
}
