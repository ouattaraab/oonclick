<?php

namespace App\Http\Controllers;

use App\Models\FeatureSetting;
use App\Models\ReferralEarning;
use App\Models\SubscriberProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ReferralController extends Controller
{
    /**
     * Retourne l'arbre de parrainage de l'utilisateur connecté.
     *
     * Niveau 1 : filleuls directs (referred_by = user->id).
     * Niveau 2 : filleuls des filleuls de niveau 1.
     * Inclut les gains cumulés par niveau et la configuration feature.
     */
    public function tree(Request $request): JsonResponse
    {
        $user = $request->user();

        // ── Niveau 1 : filleuls directs ─────────────────────────────────────
        $level1 = SubscriberProfile::where('referred_by', $user->id)
            ->with('user:id,name,phone,created_at')
            ->get()
            ->map(fn ($p) => [
                'user_id'   => $p->user_id,
                'name'      => $p->user?->name ?? $p->user?->phone ?? 'Inconnu',
                'joined_at' => $p->user?->created_at?->toISOString(),
            ]);

        // ── Niveau 2 : filleuls des filleuls ────────────────────────────────
        $level1Ids = $level1->pluck('user_id');
        $level2    = collect();

        if ($level1Ids->isNotEmpty()) {
            $level2 = SubscriberProfile::whereIn('referred_by', $level1Ids)
                ->with('user:id,name,phone,created_at')
                ->get()
                ->map(fn ($p) => [
                    'user_id'     => $p->user_id,
                    'name'        => $p->user?->name ?? $p->user?->phone ?? 'Inconnu',
                    'referred_by' => $p->referred_by,
                    'joined_at'   => $p->user?->created_at?->toISOString(),
                ]);
        }

        // ── Gains par niveau ─────────────────────────────────────────────────
        $earnings = ReferralEarning::where('referrer_id', $user->id)
            ->selectRaw('level, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('level')
            ->get()
            ->keyBy('level');

        // ── Config feature ───────────────────────────────────────────────────
        $multiLevelEnabled = FeatureSetting::isEnabled('referral_levels');
        $config            = $multiLevelEnabled ? FeatureSetting::getConfig('referral_levels') : [];

        return response()->json([
            'level_1' => [
                'referrals' => $level1,
                'count'     => $level1->count(),
                'earnings'  => (int) ($earnings[1]->total ?? 0),
            ],
            'level_2' => [
                'referrals' => $level2,
                'count'     => $level2->count(),
                'earnings'  => (int) ($earnings[2]->total ?? 0),
            ],
            'config'               => $config,
            'multi_level_enabled'  => $multiLevelEnabled,
        ]);
    }
}
