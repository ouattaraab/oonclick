<?php

namespace Database\Seeders;

use App\Models\FeatureSetting;
use Illuminate\Database\Seeder;

class FeatureSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            [
                'feature_slug' => 'streak',
                'label'        => 'Streak de connexion amélioré',
                'description'  => 'Bonus croissant pour les connexions quotidiennes consécutives avec multiplicateur de gains.',
                'is_enabled'   => false,
                'config'       => json_encode(['bonus_schedule' => [50, 75, 100, 125, 150, 175, 200], 'weekly_bonus' => 500, 'streak_multiplier' => 1.10, 'streak_multiplier_threshold' => 7]),
                'sort_order'   => 1,
            ],
            [
                'feature_slug' => 'levels',
                'label'        => 'Niveaux enrichis avec avantages',
                'description'  => 'Système de niveaux Bronze→Légende avec plus de pubs/jour, multiplicateur de gains et retrait minimum réduit.',
                'is_enabled'   => false,
                'config'       => json_encode(['levels' => [
                    ['level' => 1, 'name' => 'Bronze',  'max_views' => 10, 'multiplier' => 1.0,  'min_withdrawal' => 5000],
                    ['level' => 2, 'name' => 'Silver',  'max_views' => 15, 'multiplier' => 1.05, 'min_withdrawal' => 3000],
                    ['level' => 3, 'name' => 'Gold',    'max_views' => 20, 'multiplier' => 1.10, 'min_withdrawal' => 2000],
                    ['level' => 4, 'name' => 'Diamond', 'max_views' => 25, 'multiplier' => 1.15, 'min_withdrawal' => 1000],
                    ['level' => 5, 'name' => 'Légende', 'max_views' => 30, 'multiplier' => 1.20, 'min_withdrawal' => 500],
                ]]),
                'sort_order'   => 2,
            ],
            [
                'feature_slug' => 'surveys',
                'label'        => 'Sondages rémunérés',
                'description'  => 'Permet aux abonnés de répondre à des sondages pour gagner des FCFA.',
                'is_enabled'   => false,
                'config'       => json_encode(['default_reward' => 200, 'default_xp' => 20, 'max_surveys_per_day' => 5, 'min_questions' => 3, 'max_questions' => 20]),
                'sort_order'   => 3,
            ],
            [
                'feature_slug' => 'missions',
                'label'        => 'Missions quotidiennes',
                'description'  => 'Tâches quotidiennes avec récompenses (regarder X pubs, check-in, inviter, etc.).',
                'is_enabled'   => false,
                'config'       => json_encode(['missions' => [
                    ['slug' => 'watch_5',  'title' => 'Regarder 5 pubs',       'type' => 'views',    'target' => 5,  'reward_fcfa' => 100, 'reward_xp' => 20],
                    ['slug' => 'checkin',  'title' => 'Check-in du jour',       'type' => 'checkin',  'target' => 1,  'reward_fcfa' => 50,  'reward_xp' => 10],
                    ['slug' => 'invite_1', 'title' => 'Inviter 1 ami',          'type' => 'referral', 'target' => 1,  'reward_fcfa' => 200, 'reward_xp' => 30],
                    ['slug' => 'survey_1', 'title' => 'Compléter 1 sondage',    'type' => 'survey',   'target' => 1,  'reward_fcfa' => 100, 'reward_xp' => 15],
                    ['slug' => 'watch_10', 'title' => 'Regarder 10 pubs',       'type' => 'views',    'target' => 10, 'reward_fcfa' => 250, 'reward_xp' => 50],
                ]]),
                'sort_order'   => 4,
            ],
            [
                'feature_slug' => 'cashback',
                'label'        => 'Cashback sur achats partenaires',
                'description'  => 'Les utilisateurs gagnent du cashback sur les achats chez les partenaires.',
                'is_enabled'   => false,
                'config'       => json_encode(['max_claims_per_day' => 3, 'min_claim_amount' => 500, 'auto_approve_below' => 1000]),
                'sort_order'   => 5,
            ],
            [
                'feature_slug' => 'referral_levels',
                'label'        => 'Parrainage multi-niveaux',
                'description'  => 'Bonus de parrainage sur 2 niveaux (direct + indirect).',
                'is_enabled'   => false,
                'config'       => json_encode(['level_1_bonus' => 200, 'level_2_bonus' => 50, 'level_2_enabled' => true, 'max_levels' => 2]),
                'sort_order'   => 6,
            ],
            [
                'feature_slug' => 'offline_mode',
                'label'        => 'Mode hors-ligne partiel',
                'description'  => 'Pré-chargement de campagnes pour visionnage sans connexion.',
                'is_enabled'   => false,
                'config'       => json_encode(['max_preload_campaigns' => 5, 'preload_validity_hours' => 24, 'sync_max_batch_size' => 10]),
                'sort_order'   => 7,
            ],
            [
                'feature_slug' => 'coupons',
                'label'        => 'Marketplace de coupons',
                'description'  => 'Coupons de réduction collectés automatiquement après visionnage de pubs.',
                'is_enabled'   => false,
                'config'       => json_encode(['auto_collect_on_view' => true, 'max_coupons_per_user' => 20]),
                'sort_order'   => 8,
            ],
            [
                'feature_slug' => 'leaderboard_enhanced',
                'label'        => 'Classements sociaux améliorés',
                'description'  => 'Leaderboard avec tabs hebdo/mensuel, filtre par ville, et prix pour le top 3.',
                'is_enabled'   => false,
                'config'       => json_encode(['weekly_prizes' => [1000, 500, 250], 'show_city_filter' => true, 'show_weekly_tab' => true, 'show_monthly_tab' => true]),
                'sort_order'   => 9,
            ],
        ];

        foreach ($features as $feature) {
            FeatureSetting::updateOrCreate(
                ['feature_slug' => $feature['feature_slug']],
                $feature
            );
        }
    }
}
