<?php

namespace App\Http\Controllers;

use App\Models\CashbackClaim;
use App\Models\FeatureSetting;
use App\Models\PartnerOffer;
use App\Modules\Payment\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gère les offres partenaires et les demandes de cashback.
 */
class OfferController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    /**
     * GET /api/offers
     *
     * Retourne la liste des offres partenaires actives et non expirées.
     * Nécessite que la fonctionnalité 'cashback' soit activée.
     */
    public function index(): JsonResponse
    {
        if (! FeatureSetting::isEnabled('cashback')) {
            return response()->json(['message' => 'Fonctionnalité cashback non disponible.'], 403);
        }

        $offers = PartnerOffer::active()
            ->orderByDesc('cashback_percent')
            ->get()
            ->map(fn (PartnerOffer $offer) => [
                'id'               => $offer->id,
                'partner_name'     => $offer->partner_name,
                'description'      => $offer->description,
                'logo_url'         => $offer->logo_url,
                'cashback_percent' => (float) $offer->cashback_percent,
                'promo_code'       => $offer->promo_code,
                'category'         => $offer->category,
                'expires_at'       => $offer->expires_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $offers]);
    }

    /**
     * POST /api/offers/{id}/claim
     *
     * Soumet une demande de cashback pour une offre partenaire.
     *
     * Corps attendu :
     * - purchase_amount     : montant d'achat en FCFA (entier >= 1)
     * - receipt_reference   : référence de reçu (optionnelle)
     *
     * Si le montant est inférieur au seuil d'approbation automatique configuré,
     * la demande est approuvée et créditée immédiatement.
     */
    public function claim(Request $request, int $id): JsonResponse
    {
        if (! FeatureSetting::isEnabled('cashback')) {
            return response()->json(['message' => 'Fonctionnalité cashback non disponible.'], 403);
        }

        $request->validate([
            'purchase_amount'   => 'required|integer|min:1',
            'receipt_reference' => 'nullable|string|max:255',
        ]);

        $offer = PartnerOffer::active()->findOrFail($id);

        $purchaseAmount  = (int) $request->input('purchase_amount');
        $cashbackAmount  = (int) floor($purchaseAmount * ($offer->cashback_percent / 100));

        $cashbackConfig  = FeatureSetting::getConfig('cashback');
        $autoApproveBelow = (int) ($cashbackConfig['auto_approve_below'] ?? 0);

        $status = 'pending';

        $claim = DB::transaction(function () use ($request, $offer, $purchaseAmount, $cashbackAmount, $autoApproveBelow, &$status) {
            $claim = CashbackClaim::create([
                'user_id'           => $request->user()->id,
                'offer_id'          => $offer->id,
                'purchase_amount'   => $purchaseAmount,
                'cashback_amount'   => $cashbackAmount,
                'receipt_reference' => $request->input('receipt_reference'),
                'status'            => 'pending',
            ]);

            // Auto-approbation si montant inférieur au seuil configuré
            if ($autoApproveBelow > 0 && $cashbackAmount < $autoApproveBelow) {
                $claim->update(['status' => 'credited']);
                $status = 'credited';

                $this->walletService->credit(
                    $request->user()->id,
                    $cashbackAmount,
                    'credit',
                    "Cashback offre #{$offer->id} — {$offer->partner_name}",
                    [
                        'offer_id'  => $offer->id,
                        'claim_id'  => $claim->id,
                        'type'      => 'cashback',
                    ]
                );
            }

            return $claim;
        });

        $message = $status === 'credited'
            ? "Cashback de {$cashbackAmount} FCFA crédité automatiquement."
            : 'Votre demande de cashback est en cours de vérification.';

        return response()->json([
            'message'        => $message,
            'claim_id'       => $claim->id,
            'cashback_amount' => $cashbackAmount,
            'status'         => $status,
        ], 201);
    }
}
