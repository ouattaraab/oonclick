<?php

namespace App\Http\Controllers;

use App\Models\FeatureSetting;
use App\Models\User;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur de gamification (US-050).
 *
 * Expose le profil XP/niveau, la liste des badges et le classement.
 */
class GamificationController extends Controller
{
    public function __construct(
        private readonly GamificationService $gamificationService,
    ) {}

    /**
     * GET /api/gamification/profile
     *
     * Retourne le profil gamification de l'utilisateur authentifié :
     *   - Points XP
     *   - Niveau courant
     *   - Badges gagnés
     *   - Prochain badge à débloquer
     *   - Progression en pourcentage vers le niveau suivant
     *   - Bénéfices du niveau courant (si feature 'levels' activée)
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load('badges');

        $levelProgress = $this->gamificationService->getLevelProgress($user);
        $badges        = $user->badges->map(fn ($b) => [
            'id'           => $b->id,
            'name'         => $b->name,
            'display_name' => $b->display_name,
            'description'  => $b->description,
            'icon'         => $b->icon,
            'category'     => $b->category,
            'level'        => $b->level,
            'earned_at'    => $b->pivot->earned_at,
        ]);

        $response = [
            'xp'               => $user->xp_points,
            'level'            => $levelProgress['current_level'],
            'next_level'       => $levelProgress['next_level'],
            'xp_for_next'      => $levelProgress['xp_for_next'],
            'progress_percent' => $levelProgress['progress_percent'],
            'badges'           => $badges,
            'badges_count'     => $badges->count(),
        ];

        // Ajouter les bénéfices du niveau si la feature 'levels' est activée
        if (FeatureSetting::isEnabled('levels')) {
            $levelConfig = FeatureSetting::getConfig('levels');
            $levelData   = collect($levelConfig['levels'] ?? [])->firstWhere('level', $levelProgress['current_level']);

            $response['level_benefits'] = $levelData;
            $response['all_levels']     = $levelConfig['levels'] ?? [];
        }

        return response()->json($response);
    }

    /**
     * GET /api/gamification/badges
     *
     * Retourne tous les badges de la plateforme avec leur statut "obtenu ou non"
     * pour l'utilisateur authentifié.
     */
    public function badges(Request $request): JsonResponse
    {
        $user         = $request->user();
        $earnedBadges = $user->badges()->pluck('badges.id')->toArray();

        $badges = \App\Models\Badge::orderBy('level')->orderBy('xp_required')->get()
            ->map(fn ($badge) => [
                'id'           => $badge->id,
                'name'         => $badge->name,
                'display_name' => $badge->display_name,
                'description'  => $badge->description,
                'icon'         => $badge->icon,
                'xp_required'  => $badge->xp_required,
                'level'        => $badge->level,
                'category'     => $badge->category,
                'earned'       => in_array($badge->id, $earnedBadges, true),
            ]);

        return response()->json([
            'badges' => $badges,
        ]);
    }

    /**
     * GET /api/gamification/leaderboard
     *
     * Classement des subscribers par XP (all-time), par vues de la semaine,
     * ou par vues du mois. Supporte le filtre par ville si la feature
     * 'leaderboard_enhanced' est activée.
     *
     * Query params :
     *   - period : 'all' (défaut) | 'week' | 'month'
     *   - city   : filtre optionnel (activé via config)
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $user   = $request->user();
        $config = FeatureSetting::isEnabled('leaderboard_enhanced')
            ? FeatureSetting::getConfig('leaderboard_enhanced')
            : [];

        $period = $request->query('period', 'all'); // week, month, all
        $city   = $request->query('city');

        $query = User::where('role', 'subscriber')->where('is_active', true);

        // Filtre optionnel par ville (si la config l'autorise)
        if ($city && ($config['show_city_filter'] ?? false)) {
            $query->whereHas('profile', fn ($q) => $q->where('city', $city));
        }

        // Tri et scoring selon la période choisie
        if ($period === 'week' && ($config['show_weekly_tab'] ?? false)) {
            $startOfWeek = now()->startOfWeek();
            $query->withCount(['adViews as period_score' => fn ($q) =>
                $q->where('is_completed', true)->where('completed_at', '>=', $startOfWeek)
            ])->orderByDesc('period_score');
        } elseif ($period === 'month' && ($config['show_monthly_tab'] ?? false)) {
            $startOfMonth = now()->startOfMonth();
            $query->withCount(['adViews as period_score' => fn ($q) =>
                $q->where('is_completed', true)->where('completed_at', '>=', $startOfMonth)
            ])->orderByDesc('period_score');
        } else {
            // Classement all-time par XP (comportement par défaut)
            $period = 'all';
            $query->orderByDesc('xp_points');
        }

        $leaderboard = $query->limit(20)->get()->map(function ($u, $idx) use ($period) {
            return [
                'rank'    => $idx + 1,
                'user_id' => $u->id,
                'name'    => $u->name ?? $u->phone,
                'xp'      => $u->xp_points,
                'score'   => $period !== 'all' ? ($u->period_score ?? 0) : $u->xp_points,
                'level'   => $this->gamificationService->getUserLevel($u),
                'city'    => $u->profile?->city,
            ];
        });

        // Calculer le rang et le score de l'utilisateur courant
        if ($period === 'week') {
            $startDate = now()->startOfWeek();
            $myScore   = $user->adViews()->where('is_completed', true)->where('completed_at', '>=', $startDate)->count();
            $myRank    = User::where('role', 'subscriber')->where('is_active', true)
                ->withCount(['adViews as period_score' => fn ($q) =>
                    $q->where('is_completed', true)->where('completed_at', '>=', $startDate)
                ])
                ->having('period_score', '>', $myScore)
                ->count() + 1;
        } elseif ($period === 'month') {
            $startDate = now()->startOfMonth();
            $myScore   = $user->adViews()->where('is_completed', true)->where('completed_at', '>=', $startDate)->count();
            $myRank    = User::where('role', 'subscriber')->where('is_active', true)
                ->withCount(['adViews as period_score' => fn ($q) =>
                    $q->where('is_completed', true)->where('completed_at', '>=', $startDate)
                ])
                ->having('period_score', '>', $myScore)
                ->count() + 1;
        } else {
            $myRank  = User::where('role', 'subscriber')->where('is_active', true)
                ->where('xp_points', '>', $user->xp_points)->count() + 1;
            $myScore = $user->xp_points;
        }

        return response()->json([
            'leaderboard' => $leaderboard,
            'my_rank'     => $myRank,
            'my_xp'       => $user->xp_points,
            'my_score'    => $myScore,
            'period'      => $period,
            'config'      => [
                'show_city_filter'  => $config['show_city_filter'] ?? false,
                'show_weekly_tab'   => $config['show_weekly_tab'] ?? false,
                'show_monthly_tab'  => $config['show_monthly_tab'] ?? false,
                'weekly_prizes'     => $config['weekly_prizes'] ?? [],
            ],
        ]);
    }
}
