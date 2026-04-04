<?php

namespace App\Http\Controllers;

use App\Models\DailyCheckin;
use App\Models\FeatureSetting;
use App\Models\Wallet;
use App\Modules\Payment\Services\WalletService;
use App\Services\GamificationService;
use App\Services\MissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Gère le système de check-in quotidien avec bonus et streak (US-049).
 *
 * Règle des bonus par streak (fallback statique) :
 *   Jour 1 → 10 FCFA | Jour 2 → 15 | Jour 3 → 20 | Jour 4 → 25
 *   Jour 5 → 30 | Jour 6 → 40 | Jour 7+ → 50 FCFA
 *
 * Quand la feature 'streak' est activée, le barème et les bonus hebdomadaires
 * sont lus dynamiquement depuis FeatureSetting.
 */
class DailyCheckinController extends Controller
{
    /**
     * Barème des bonus fallback selon le jour de streak.
     */
    private const STREAK_BONUSES = [
        1 => 10,
        2 => 15,
        3 => 20,
        4 => 25,
        5 => 30,
        6 => 40,
    ];

    /**
     * Bonus fallback à partir du 7ème jour consécutif.
     */
    private const MAX_STREAK_BONUS = 50;

    public function __construct(
        private readonly WalletService $walletService,
        private readonly GamificationService $gamificationService,
    ) {}

    /**
     * POST /api/checkin
     *
     * Enregistre le check-in du jour pour l'utilisateur authentifié,
     * calcule le streak et crédite le bonus correspondant sur son wallet.
     */
    public function checkin(Request $request): JsonResponse
    {
        $user  = $request->user();
        $today = now()->toDateString();

        // Vérifier si l'utilisateur a déjà fait son check-in aujourd'hui
        $alreadyCheckedIn = DailyCheckin::where('user_id', $user->id)
            ->where('checked_in_at', $today)
            ->exists();

        if ($alreadyCheckedIn) {
            return response()->json([
                'message' => 'Déjà fait aujourd\'hui.',
            ], 409);
        }

        // Calculer le streak : compter les jours consécutifs depuis hier
        $streakDay = $this->calculateStreak($user->id);

        // Lire la config dynamique si la feature streak est activée
        $streakEnabled   = FeatureSetting::isEnabled('streak');
        $streakConfig    = $streakEnabled ? FeatureSetting::getConfig('streak') : [];
        $bonusSchedule   = $streakConfig['bonus_schedule'] ?? null;
        $weeklyBonus     = $streakConfig['weekly_bonus'] ?? 0;

        // Déterminer le montant du bonus selon la config active
        if ($streakEnabled && $bonusSchedule !== null && count($bonusSchedule) > 0) {
            $dayIndex    = min($streakDay - 1, count($bonusSchedule) - 1);
            $bonusAmount = (int) $bonusSchedule[$dayIndex];
        } else {
            // Fallback sur le barème statique
            $bonusAmount = self::STREAK_BONUSES[$streakDay] ?? self::MAX_STREAK_BONUS;
        }

        // Créditer le wallet de l'utilisateur
        $this->walletService->credit(
            userId: $user->id,
            amount: $bonusAmount,
            type: 'bonus',
            description: "Bonus check-in quotidien — jour {$streakDay} de série",
            metadata: [
                'streak_day'    => $streakDay,
                'checked_in_at' => $today,
            ]
        );

        // Enregistrer le check-in
        DailyCheckin::create([
            'user_id'       => $user->id,
            'checked_in_at' => $today,
            'bonus_amount'  => $bonusAmount,
            'streak_day'    => $streakDay,
        ]);

        // Bonus hebdomadaire : créditer un montant supplémentaire chaque multiple de 7
        if ($streakEnabled && $weeklyBonus > 0 && $streakDay > 0 && $streakDay % 7 === 0) {
            $weekNumber = (int) ($streakDay / 7);
            try {
                $this->walletService->credit(
                    userId: $user->id,
                    amount: $weeklyBonus,
                    type: 'bonus',
                    description: "Bonus hebdomadaire streak semaine {$weekNumber}",
                    metadata: [
                        'streak_day'   => $streakDay,
                        'week_number'  => $weekNumber,
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning("Weekly streak bonus credit failed for user#{$user->id}: {$e->getMessage()}");
            }
        }

        // Attribuer les XP de check-in — +5 XP par check-in quotidien (US-050)
        try {
            $this->gamificationService->awardXp($user, 5, "Check-in quotidien — jour {$streakDay}");
        } catch (\Throwable $e) {
            Log::warning("GamificationService XP award failed for checkin user#{$user->id}: {$e->getMessage()}");
        }

        // Incrémenter la progression des missions de type 'checkin'
        try {
            app(MissionService::class)->incrementProgress($user, 'checkin');
        } catch (\Throwable $e) {
            Log::warning("MissionService incrementProgress failed for checkin user#{$user->id}: {$e->getMessage()}");
        }

        // Récupérer le nouveau solde
        $newBalance = $this->walletService->getBalance($user->id);

        $response = [
            'message'        => 'Check-in effectué avec succès.',
            'streak_day'     => $streakDay,
            'bonus_amount'   => $bonusAmount,
            'new_balance'    => $newBalance,
            'streak_enabled' => $streakEnabled,
        ];

        // Ajouter le bonus hebdomadaire dans la réponse si applicable
        if ($streakEnabled && $weeklyBonus > 0 && $streakDay % 7 === 0) {
            $response['weekly_bonus_awarded'] = $weeklyBonus;
            $response['week_number']          = (int) ($streakDay / 7);
        }

        return response()->json($response, 201);
    }

    /**
     * GET /api/checkin/status
     *
     * Retourne l'état du check-in de l'utilisateur authentifié :
     * a-t-il coché aujourd'hui, quel est son streak, etc.
     */
    public function status(Request $request): JsonResponse
    {
        $user  = $request->user();
        $today = now()->toDateString();

        // Vérifier le check-in du jour
        $todayCheckin = DailyCheckin::where('user_id', $user->id)
            ->where('checked_in_at', $today)
            ->first();

        // Dernier check-in connu
        $lastCheckin = DailyCheckin::where('user_id', $user->id)
            ->latest('checked_in_at')
            ->first();

        // Streak courant (recalculé depuis le dernier check-in)
        $currentStreak = $todayCheckin
            ? $todayCheckin->streak_day
            : $this->calculateStreak($user->id, includeToday: false);

        // Totaux
        $totalCheckins    = DailyCheckin::where('user_id', $user->id)->count();
        $totalBonusEarned = DailyCheckin::where('user_id', $user->id)->sum('bonus_amount');

        return response()->json([
            'checked_in_today'   => $todayCheckin !== null,
            'current_streak'     => $currentStreak,
            'last_checkin_date'  => $lastCheckin?->checked_in_at?->toDateString(),
            'total_checkins'     => $totalCheckins,
            'total_bonus_earned' => (int) $totalBonusEarned,
        ]);
    }

    /**
     * GET /api/checkin/calendar
     *
     * Retourne l'historique des check-ins des 30 derniers jours
     * avec la configuration streak active.
     */
    public function calendar(Request $request): JsonResponse
    {
        $user = $request->user();

        $checkins = DailyCheckin::where('user_id', $user->id)
            ->where('checked_in_at', '>=', now()->subDays(30))
            ->orderBy('checked_in_at')
            ->get(['checked_in_at', 'streak_day', 'bonus_amount']);

        $streakEnabled = FeatureSetting::isEnabled('streak');
        $streakConfig  = $streakEnabled ? FeatureSetting::getConfig('streak') : [];

        return response()->json([
            'calendar'       => $checkins,
            'streak_config'  => $streakConfig,
            'streak_enabled' => $streakEnabled,
        ]);
    }

    // =========================================================================
    // Méthodes privées
    // =========================================================================

    /**
     * Calcule la longueur de la série consécutive de check-ins.
     *
     * Remonte jour par jour depuis hier (ou avant-hier si includeToday=false
     * et qu'il n'y a pas eu de check-in aujourd'hui) pour compter combien
     * de jours consécutifs l'utilisateur a déjà cochés.
     * Le résultat est le numéro du PROCHAIN jour de streak (celui d'aujourd'hui).
     *
     * @param bool $includeToday  Si true, on cherche le streak pour aujourd'hui.
     *                            Si false, on retourne le streak actif sans
     *                            compter le jour en cours.
     */
    private function calculateStreak(int $userId, bool $includeToday = true): int
    {
        // Point de départ : hier (on construit le streak pour aujourd'hui)
        $checkDate = now()->subDay()->toDateString();
        $streak    = 0;

        // Remonter les jours consécutifs depuis hier
        while (true) {
            $exists = DailyCheckin::where('user_id', $userId)
                ->where('checked_in_at', $checkDate)
                ->exists();

            if (! $exists) {
                break;
            }

            $streak++;
            $checkDate = now()->subDays($streak + 1)->toDateString();
        }

        // Le jour actuel sera le (streak + 1)ème jour de série
        return $includeToday ? $streak + 1 : $streak;
    }
}
