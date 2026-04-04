<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\DailyCheckin;
use App\Models\FeatureSetting;
use App\Models\SubscriberProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de gamification (US-050).
 *
 * Gère l'attribution des points XP, le déblocage des badges,
 * et le calcul du niveau utilisateur.
 *
 * Niveaux XP :
 *   Niveau 1 → 0 XP      (Nouveau)
 *   Niveau 2 → 100 XP    (Explorateur)
 *   Niveau 3 → 500 XP    (Fidèle)
 *   Niveau 4 → 1 500 XP  (Expert)
 *   Niveau 5 → 5 000 XP  (Légende)
 */
class GamificationService
{
    /**
     * Paliers XP par niveau.
     */
    private const LEVEL_THRESHOLDS = [
        1 => 0,
        2 => 100,
        3 => 500,
        4 => 1500,
        5 => 5000,
    ];

    /**
     * Ajoute des XP à un utilisateur et vérifie si de nouveaux badges sont débloqués.
     *
     * @param User   $user   Utilisateur à créditer
     * @param int    $amount Nombre de points XP à ajouter
     * @param string $reason Raison (pour le log)
     * @return int           Nouveau total XP
     */
    public function awardXp(User $user, int $amount, string $reason): int
    {
        if ($amount <= 0) {
            return $user->xp_points;
        }

        // Incrément atomique pour éviter les race conditions
        DB::table('users')
            ->where('id', $user->id)
            ->increment('xp_points', $amount);

        $user->xp_points += $amount;

        Log::info("GamificationService : +{$amount} XP pour user#{$user->id} — {$reason}");

        // Vérifier si de nouveaux badges sont débloqués après l'attribution
        $this->checkBadges($user);

        return $user->xp_points;
    }

    /**
     * Compare les XP et les statistiques de l'utilisateur avec les critères de chaque badge,
     * et attribue ceux qui ne sont pas encore gagnés.
     *
     * @param User $user Utilisateur à évaluer
     * @return array     Liste des nouveaux badges débloqués (instances Badge)
     */
    public function checkBadges(User $user): array
    {
        // Recharger les XP depuis la DB pour avoir la valeur fraîche
        $user->refresh();

        // IDs des badges déjà obtenus
        $earnedBadgeIds = $user->badges()->pluck('badges.id')->toArray();

        // Tous les badges de la plateforme
        $allBadges = Badge::all();

        $newBadges = [];

        foreach ($allBadges as $badge) {
            // Ne pas re-décerner un badge déjà obtenu
            if (in_array($badge->id, $earnedBadgeIds, true)) {
                continue;
            }

            if ($this->userMeetsBadgeCriteria($user, $badge)) {
                // Attribuer le badge
                $user->badges()->attach($badge->id, ['earned_at' => now()]);
                $newBadges[] = $badge;

                Log::info("GamificationService : badge '{$badge->name}' attribué à user#{$user->id}");
            }
        }

        return $newBadges;
    }

    /**
     * Retourne le niveau courant de l'utilisateur en fonction de ses XP.
     *
     * @param User $user Utilisateur
     * @return int       Niveau (1 à 5)
     */
    public function getUserLevel(User $user): int
    {
        $xp    = $user->xp_points;
        $level = 1;

        foreach (self::LEVEL_THRESHOLDS as $lvl => $threshold) {
            if ($xp >= $threshold) {
                $level = $lvl;
            }
        }

        return $level;
    }

    /**
     * Retourne les infos de progression vers le prochain niveau.
     *
     * @param User $user
     * @return array{current_level: int, next_level: int|null, xp_for_next: int|null, progress_percent: int}
     */
    public function getLevelProgress(User $user): array
    {
        $xp           = $user->xp_points;
        $currentLevel = $this->getUserLevel($user);
        $nextLevel    = $currentLevel + 1;

        if (! isset(self::LEVEL_THRESHOLDS[$nextLevel])) {
            // Niveau maximum atteint
            return [
                'current_level'   => $currentLevel,
                'next_level'      => null,
                'xp_for_next'     => null,
                'progress_percent' => 100,
            ];
        }

        $currentThreshold = self::LEVEL_THRESHOLDS[$currentLevel];
        $nextThreshold    = self::LEVEL_THRESHOLDS[$nextLevel];
        $range            = $nextThreshold - $currentThreshold;
        $progress         = $xp - $currentThreshold;
        $percent          = $range > 0 ? (int) min(100, round(($progress / $range) * 100)) : 100;

        return [
            'current_level'   => $currentLevel,
            'next_level'      => $nextLevel,
            'xp_for_next'     => $nextThreshold - $xp,
            'progress_percent' => $percent,
        ];
    }

    // =========================================================================
    // Méthodes privées — critères de déblocage
    // =========================================================================

    /**
     * Vérifie si l'utilisateur remplit les critères pour obtenir un badge donné.
     */
    private function userMeetsBadgeCriteria(User $user, Badge $badge): bool
    {
        return match ($badge->name) {
            // Niveau 1 : attribué à l'inscription (0 XP suffisent)
            'nouveau' => true,

            // Niveaux basés uniquement sur les XP
            'explorateur', 'expert', 'legende' => $user->xp_points >= $badge->xp_required,

            // Niveau 3 : XP + 7 jours de streak minimum
            'fidele' => $user->xp_points >= $badge->xp_required
                && $this->getMaxStreak($user->id) >= 7,

            // Badge Parrain : 5 filleuls actifs
            'parrain' => $this->getReferralCount($user->id) >= 5,

            // Badge Flambeur : 30 jours consécutifs
            'flambeur' => $this->getMaxStreak($user->id) >= 30,

            // Badges de flamme streak enrichis (feature 'streak')
            'flamme_7'   => $this->getMaxStreak($user->id) >= 7,
            'flamme_30'  => $this->getMaxStreak($user->id) >= 30,
            'flamme_100' => $this->getMaxStreak($user->id) >= 100,

            // Critère inconnu : ne pas attribuer
            default => false,
        };
    }

    /**
     * Retourne le nombre de filleuls actifs de l'utilisateur.
     */
    private function getReferralCount(int $userId): int
    {
        return SubscriberProfile::where('referred_by', $userId)->count();
    }

    /**
     * Retourne le streak maximal atteint par un utilisateur (en jours consécutifs).
     * Calcule la plus longue série depuis le début de l'historique.
     */
    private function getMaxStreak(int $userId): int
    {
        $checkins = DailyCheckin::where('user_id', $userId)
            ->orderBy('checked_in_at')
            ->pluck('checked_in_at')
            ->map(fn ($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->toArray();

        if (empty($checkins)) {
            return 0;
        }

        $maxStreak     = 1;
        $currentStreak = 1;

        for ($i = 1; $i < count($checkins); $i++) {
            $prev = \Carbon\Carbon::parse($checkins[$i - 1]);
            $curr = \Carbon\Carbon::parse($checkins[$i]);

            if ($prev->addDay()->toDateString() === $curr->toDateString()) {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
            } else {
                $currentStreak = 1;
            }
        }

        return $maxStreak;
    }
}
