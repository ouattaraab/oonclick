<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\FeatureSetting;
use App\Models\UserCoupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Gère les coupons collectés par les abonnés.
 */
class CouponController extends Controller
{
    /**
     * GET /api/coupons
     *
     * Retourne les coupons collectés par l'utilisateur authentifié.
     * Nécessite que la fonctionnalité 'coupons' soit activée.
     */
    public function index(Request $request): JsonResponse
    {
        if (! FeatureSetting::isEnabled('coupons')) {
            return response()->json(['message' => 'Fonctionnalité coupons non disponible.'], 403);
        }

        $userCoupons = UserCoupon::with('coupon')
            ->where('user_id', $request->user()->id)
            ->latest('collected_at')
            ->get()
            ->map(fn (UserCoupon $uc) => [
                'id'           => $uc->id,
                'is_used'      => $uc->is_used,
                'collected_at' => $uc->collected_at->toIso8601String(),
                'used_at'      => $uc->used_at?->toIso8601String(),
                'coupon'       => $uc->coupon ? [
                    'id'             => $uc->coupon->id,
                    'code'           => $uc->coupon->code,
                    'description'    => $uc->coupon->description,
                    'discount_type'  => $uc->coupon->discount_type,
                    'discount_value' => $uc->coupon->discount_value,
                    'discount_label' => $uc->coupon->getDiscountLabel(),
                    'partner_name'   => $uc->coupon->partner_name,
                    'expires_at'     => $uc->coupon->expires_at?->toIso8601String(),
                    'is_active'      => $uc->coupon->is_active,
                ] : null,
            ]);

        return response()->json(['data' => $userCoupons]);
    }

    /**
     * POST /api/coupons/{id}/use
     *
     * Marque un coupon de l'utilisateur comme utilisé.
     */
    public function markUsed(Request $request, int $id): JsonResponse
    {
        if (! FeatureSetting::isEnabled('coupons')) {
            return response()->json(['message' => 'Fonctionnalité coupons non disponible.'], 403);
        }

        $userCoupon = UserCoupon::with('coupon')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($userCoupon->is_used) {
            return response()->json(['message' => 'Ce coupon a déjà été utilisé.'], 409);
        }

        DB::transaction(function () use ($userCoupon) {
            $userCoupon->update([
                'is_used' => true,
                'used_at' => now(),
            ]);

            // Incrémenter le compteur d'utilisations du coupon
            if ($userCoupon->coupon) {
                Coupon::where('id', $userCoupon->coupon_id)->increment('uses_count');
            }
        });

        return response()->json([
            'message' => 'Coupon marqué comme utilisé.',
            'code'    => $userCoupon->coupon?->code,
        ]);
    }
}
