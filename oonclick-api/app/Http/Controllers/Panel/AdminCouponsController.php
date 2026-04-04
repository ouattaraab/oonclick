<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Coupon;
use Illuminate\Http\Request;

class AdminCouponsController extends Controller
{
    /**
     * GET /panel/admin/coupons
     *
     * Affiche tous les coupons.
     */
    public function index()
    {
        $coupons       = Coupon::with('campaign')->latest()->paginate(20);
        $totalCoupons  = Coupon::count();
        $activeCoupons = Coupon::where('is_active', true)->count();
        $totalUses     = Coupon::sum('uses_count');
        $campaigns     = Campaign::where('status', 'active')->orWhere('status', 'completed')->latest()->get(['id', 'title']);

        return view('panel.admin.coupons-admin', compact(
            'coupons',
            'totalCoupons',
            'activeCoupons',
            'totalUses',
            'campaigns'
        ));
    }

    /**
     * POST /panel/admin/coupons
     *
     * Crée un nouveau coupon.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code'           => 'required|string|max:100|unique:coupons,code',
            'description'    => 'nullable|string',
            'discount_type'  => 'required|in:percent,fixed',
            'discount_value' => 'required|integer|min:1',
            'partner_name'   => 'required|string|max:255',
            'campaign_id'    => 'nullable|exists:campaigns,id',
            'expires_at'     => 'nullable|date|after:now',
            'max_uses'       => 'nullable|integer|min:1',
        ]);

        Coupon::create([
            'campaign_id'    => $request->filled('campaign_id') ? $request->input('campaign_id') : null,
            'code'           => strtoupper($request->input('code')),
            'description'    => $request->input('description'),
            'discount_type'  => $request->input('discount_type'),
            'discount_value' => (int) $request->input('discount_value'),
            'partner_name'   => $request->input('partner_name'),
            'expires_at'     => $request->filled('expires_at') ? $request->input('expires_at') : null,
            'max_uses'       => $request->filled('max_uses') ? (int) $request->input('max_uses') : null,
            'uses_count'     => 0,
            'is_active'      => true,
        ]);

        return redirect()->route('panel.admin.coupons')->with('success', 'Coupon créé avec succès.');
    }

    /**
     * POST /panel/admin/coupons/{coupon}/toggle
     *
     * Active ou désactive un coupon.
     */
    public function toggleActive(Coupon $coupon)
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        $state = $coupon->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Coupon « {$coupon->code} » {$state}.");
    }
}
