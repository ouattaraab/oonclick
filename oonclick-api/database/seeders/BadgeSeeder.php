<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

/**
 * Peuple la table badges avec les badges initiaux de la plateforme oon.click (US-050).
 *
 * Structure :
 *  - Niveaux 1 à 5 : basés sur les XP accumulés (catégorie "views")
 *  - Badges spéciaux : parrainage (referral) et streak (streak)
 */
class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            // ──────────────────────────────────────────────────────────────
            // Niveaux progressifs (débloqués par XP)
            // ──────────────────────────────────────────────────────────────
            [
                'name'         => 'nouveau',
                'display_name' => 'Nouveau',
                'description'  => 'Bienvenue sur oon.click ! Tu débutes ton aventure.',
                'icon'         => '🌱',
                'xp_required'  => 0,
                'level'        => 1,
                'category'     => 'views',
            ],
            [
                'name'         => 'explorateur',
                'display_name' => 'Explorateur',
                'description'  => '10 pubs regardées — tu commences à explorer !',
                'icon'         => '🔍',
                'xp_required'  => 100,
                'level'        => 2,
                'category'     => 'views',
            ],
            [
                'name'         => 'fidele',
                'display_name' => 'Fidèle',
                'description'  => '50 pubs regardées et 7 jours de streak — la régularité paie.',
                'icon'         => '🏅',
                'xp_required'  => 500,
                'level'        => 3,
                'category'     => 'views',
            ],
            [
                'name'         => 'expert',
                'display_name' => 'Expert',
                'description'  => '150 pubs regardées — tu maîtrises la plateforme.',
                'icon'         => '⭐',
                'xp_required'  => 1500,
                'level'        => 4,
                'category'     => 'views',
            ],
            [
                'name'         => 'legende',
                'display_name' => 'Légende',
                'description'  => '500 pubs regardées et parrainage actif — le sommet !',
                'icon'         => '👑',
                'xp_required'  => 5000,
                'level'        => 5,
                'category'     => 'views',
            ],

            // ──────────────────────────────────────────────────────────────
            // Badges spéciaux
            // ──────────────────────────────────────────────────────────────
            [
                'name'         => 'parrain',
                'display_name' => 'Parrain',
                'description'  => 'A parrainé 5 personnes avec succès.',
                'icon'         => '🤝',
                'xp_required'  => 0,
                'level'        => 0,
                'category'     => 'referral',
            ],
            [
                'name'         => 'flambeur',
                'display_name' => 'Flambeur',
                'description'  => '30 jours de check-in consécutifs — respect !',
                'icon'         => '🔥',
                'xp_required'  => 0,
                'level'        => 0,
                'category'     => 'streak',
            ],

            // ──────────────────────────────────────────────────────────────
            // Badges de flamme (streak enrichi)
            // ──────────────────────────────────────────────────────────────
            [
                'name'         => 'flamme_7',
                'display_name' => 'Flamme 7 jours',
                'description'  => '7 jours de check-in consécutifs — la flamme commence !',
                'icon'         => '🔥',
                'xp_required'  => 0,
                'level'        => 0,
                'category'     => 'streak',
            ],
            [
                'name'         => 'flamme_30',
                'display_name' => 'Flamme 30 jours',
                'description'  => '30 jours consécutifs — la flamme brûle fort !',
                'icon'         => '🔥🔥',
                'xp_required'  => 0,
                'level'        => 0,
                'category'     => 'streak',
            ],
            [
                'name'         => 'flamme_100',
                'display_name' => 'Flamme 100 jours',
                'description'  => '100 jours consécutifs — légende de la régularité !',
                'icon'         => '🔥🔥🔥',
                'xp_required'  => 0,
                'level'        => 0,
                'category'     => 'streak',
            ],
        ];

        foreach ($badges as $badgeData) {
            Badge::updateOrCreate(
                ['name' => $badgeData['name']],
                $badgeData
            );
        }

        $this->command->info('BadgeSeeder : ' . count($badges) . ' badges créés ou mis à jour.');
    }
}
