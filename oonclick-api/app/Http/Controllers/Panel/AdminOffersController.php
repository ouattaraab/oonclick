<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\CashbackClaim;
use App\Models\PartnerOffer;
use App\Modules\Payment\Services\WalletService;
use Illuminate\Http\Request;

class AdminOffersController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    /**
     * GET /panel/admin/offers
     *
     * Affiche toutes les offres partenaires et les demandes de cashback.
     */
    public function index()
    {
        $offers        = PartnerOffer::withCount('claims')->latest()->paginate(20);
        $pendingClaims = CashbackClaim::with(['user', 'offer'])->where('status', 'pending')->latest()->paginate(30);
        $totalOffers   = PartnerOffer::count();
        $activeOffers  = PartnerOffer::where('is_active', true)->count();
        $totalClaims   = CashbackClaim::count();
        $pendingCount  = CashbackClaim::where('status', 'pending')->count();

        return view('panel.admin.offers', compact(
            'offers',
            'pendingClaims',
            'totalOffers',
            'activeOffers',
            'totalClaims',
            'pendingCount'
        ));
    }

    /**
     * POST /panel/admin/offers
     *
     * Crée une nouvelle offre partenaire.
     */
    public function store(Request $request)
    {
        $request->validate([
            'partner_name'     => 'required|string|max:255',
            'description'      => 'nullable|string',
            'logo_url'         => 'nullable|url|max:500',
            'cashback_percent' => 'required|numeric|min:0.01|max:100',
            'promo_code'       => 'nullable|string|max:100',
            'category'         => 'nullable|string|max:100',
            'expires_at'       => 'nullable|date|after:now',
        ]);

        PartnerOffer::create([
            'partner_name'     => $request->input('partner_name'),
            'description'      => $request->input('description'),
            'logo_url'         => $request->input('logo_url'),
            'cashback_percent' => $request->input('cashback_percent'),
            'promo_code'       => $request->input('promo_code'),
            'category'         => $request->input('category'),
            'expires_at'       => $request->filled('expires_at') ? $request->input('expires_at') : null,
            'is_active'        => true,
        ]);

        return redirect()->route('panel.admin.offers')->with('success', 'Offre partenaire créée avec succès.');
    }

    /**
     * POST /panel/admin/offers/{offer}/toggle
     *
     * Active ou désactive une offre partenaire.
     */
    public function toggleActive(PartnerOffer $offer)
    {
        $offer->update(['is_active' => ! $offer->is_active]);

        $state = $offer->is_active ? 'activée' : 'désactivée';

        return back()->with('success', "Offre « {$offer->partner_name} » {$state}.");
    }

    /**
     * POST /panel/admin/offers/claims/{claim}/approve
     *
     * Approuve une demande de cashback et crédite l'abonné.
     */
    public function approveClaim(CashbackClaim $claim)
    {
        if (! $claim->isPending()) {
            return back()->with('error', 'Cette demande ne peut pas être approuvée.');
        }

        $claim->update([
            'status'      => 'credited',
            'approved_by' => auth()->id(),
        ]);

        $this->walletService->credit(
            $claim->user_id,
            $claim->cashback_amount,
            'credit',
            "Cashback offre #{$claim->offer_id} — {$claim->offer?->partner_name}",
            [
                'offer_id' => $claim->offer_id,
                'claim_id' => $claim->id,
                'type'     => 'cashback',
            ]
        );

        return back()->with('success', "Cashback de {$claim->cashback_amount} FCFA approuvé et crédité.");
    }

    /**
     * POST /panel/admin/offers/claims/{claim}/reject
     *
     * Rejette une demande de cashback.
     */
    public function rejectClaim(CashbackClaim $claim)
    {
        if (! $claim->isPending()) {
            return back()->with('error', 'Cette demande ne peut pas être rejetée.');
        }

        $claim->update([
            'status'      => 'rejected',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Demande de cashback rejetée.');
    }
}
