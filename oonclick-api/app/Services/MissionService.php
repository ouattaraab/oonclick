<?php

namespace App\Services;

use App\Models\FeatureSetting;
use App\Models\User;
use App\Models\UserMission;
use App\Modules\Payment\Services\WalletService;
use Illuminate\Support\Facades\DB;

class MissionService
{
    /**
     * Retourne les missions du jour pour un utilisateur, en les créant si nécessaire.
     *
     * Les définitions de missions proviennent de FeatureSetting::getConfig('missions')['missions'].
     * Chaque mission a la structure :
     *   slug, title, description, type, target, reward_fcfa, reward_xp, icon
     *
     * @param User $user
     * @return array
     */
    public function getTodayMissions(User $user): array
    {
        if (! FeatureSetting::isEnabled('missions')) {
            return [];
        }

        $config   = FeatureSetting::getConfig('missions');
        $missions = $config['missions'] ?? [];
        $today    = now()->toDateString();

        return collect($missions)->map(function ($mission) use ($user, $today) {
            $userMission = UserMission::firstOrCreate(
                [
                    'user_id'      => $user->id,
                    'mission_slug' => $mission['slug'],
                    'date'         => $today,
                ],
                ['current_progress' => 0, 'completed' => false]
            );

            return array_merge($mission, [
                'id'               => $userMission->id,
                'current_progress' => $userMission->current_progress,
                'completed'        => $userMission->completed,
                'rewarded'         => $userMission->rewarded_at !== null,
            ]);
        })->toArray();
    }

    /**
     * Incrémente la progression d'un utilisateur pour les missions du type donné.
     *
     * Appelle cette méthode depuis les hooks appropriés :
     *   - type 'views'    → après ViewTrackingService::completeView()
     *   - type 'checkin'  → après DailyCheckinController::checkin()
     *   - type 'referral' → après le crédit du parrain
     *   - type 'survey'   → après SurveyController::submit()
     *
     * @param User   $user
     * @param string $type   Type de mission (views, checkin, referral, survey, …)
     * @param int    $amount Incrément (par défaut 1)
     */
    public function incrementProgress(User $user, string $type, int $amount = 1): void
    {
        if (! FeatureSetting::isEnabled('missions')) {
            return;
        }

        $config   = FeatureSetting::getConfig('missions');
        $missions = collect($config['missions'] ?? [])->where('type', $type);
        $today    = now()->toDateString();

        foreach ($missions as $mission) {
            $userMission = UserMission::firstOrCreate(
                [
                    'user_id'      => $user->id,
                    'mission_slug' => $mission['slug'],
                    'date'         => $today,
                ],
                ['current_progress' => 0, 'completed' => false]
            );

            if ($userMission->completed) {
                continue;
            }

            $userMission->increment('current_progress', $amount);

            if ($userMission->fresh()->current_progress >= $mission['target']) {
                $userMission->update(['completed' => true]);
            }
        }
    }

    /**
     * Réclame la récompense d'une mission complétée.
     *
     * Crédite le wallet et les XP de l'utilisateur, puis marque la mission comme récompensée.
     *
     * @param User $user
     * @param int  $missionId  ID de UserMission
     * @return array{reward_fcfa: int, reward_xp: int, mission: string}
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \RuntimeException
     */
    public function claimReward(User $user, int $missionId): array
    {
        $userMission = UserMission::where('id', $missionId)
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->whereNull('rewarded_at')
            ->firstOrFail();

        $config      = FeatureSetting::getConfig('missions');
        $missionDef  = collect($config['missions'] ?? [])->firstWhere('slug', $userMission->mission_slug);

        if (! $missionDef) {
            throw new \RuntimeException('Mission non trouvée.');
        }

        $fcfa = $missionDef['reward_fcfa'] ?? 0;
        $xp   = $missionDef['reward_xp'] ?? 0;

        DB::transaction(function () use ($user, $userMission, $fcfa, $xp, $missionDef) {
            if ($fcfa > 0) {
                app(WalletService::class)->credit(
                    $user->id,
                    $fcfa,
                    'bonus',
                    "Mission : {$missionDef['title']}"
                );
            }

            if ($xp > 0) {
                app(GamificationService::class)->awardXp($user, $xp, 'mission_completion');
            }

            $userMission->update(['rewarded_at' => now()]);
        });

        return [
            'reward_fcfa' => $fcfa,
            'reward_xp'   => $xp,
            'mission'     => $missionDef['title'],
        ];
    }
}
