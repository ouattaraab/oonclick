<?php

namespace Database\Seeders;

use App\Models\PlatformConfig;
use Illuminate\Database\Seeder;

class PlatformConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            [
                'key'         => 'cost_per_view',
                'value'       => '100',
                'type'        => 'integer',
                'description' => 'Coût facturé à l\'annonceur par vue complète (FCFA)',
                'is_public'   => false,
            ],
            [
                'key'         => 'subscriber_earn_per_view',
                'value'       => '60',
                'type'        => 'integer',
                'description' => 'Montant crédité à l\'abonné par vue complète (FCFA)',
                'is_public'   => true,
            ],
            [
                'key'         => 'platform_fee_per_view',
                'value'       => '40',
                'type'        => 'integer',
                'description' => 'Commission de la plateforme par vue (FCFA)',
                'is_public'   => false,
            ],
            [
                'key'         => 'signup_bonus',
                'value'       => '500',
                'type'        => 'integer',
                'description' => 'Bonus crédité à l\'inscription (FCFA)',
                'is_public'   => true,
            ],
            [
                'key'         => 'referral_bonus',
                'value'       => '200',
                'type'        => 'integer',
                'description' => 'Bonus parrainage crédité au parrain (FCFA)',
                'is_public'   => true,
            ],
            [
                'key'         => 'min_withdrawal',
                'value'       => '5000',
                'type'        => 'integer',
                'description' => 'Montant minimum de retrait (FCFA)',
                'is_public'   => true,
            ],
            [
                'key'         => 'max_views_per_hour',
                'value'       => '10',
                'type'        => 'integer',
                'description' => 'Nombre maximum de vues rémunérées par heure par abonné',
                'is_public'   => false,
            ],
            [
                'key'         => 'max_views_per_day',
                'value'       => '30',
                'type'        => 'integer',
                'description' => 'Nombre maximum de vues rémunérées par jour par abonné',
                'is_public'   => true,
            ],
            [
                'key'         => 'min_trust_score_to_view',
                'value'       => '40',
                'type'        => 'integer',
                'description' => 'Score de confiance minimum requis pour regarder des publicités',
                'is_public'   => false,
            ],
            [
                'key'         => 'min_watch_percent',
                'value'       => '80',
                'type'        => 'integer',
                'description' => 'Pourcentage minimum de la pub à regarder pour être crédité',
                'is_public'   => false,
            ],
            [
                'key'         => 'kyc_level1_max_withdrawal',
                'value'       => '10000',
                'type'        => 'integer',
                'description' => 'Plafond de retrait mensuel pour KYC niveau 1 (FCFA)',
                'is_public'   => true,
            ],
            [
                'key'         => 'kyc_level2_max_withdrawal',
                'value'       => '100000',
                'type'        => 'integer',
                'description' => 'Plafond de retrait mensuel pour KYC niveau 2 (FCFA)',
                'is_public'   => true,
            ],
            [
                'key'         => 'kyc_level3_max_withdrawal',
                'value'       => '1000000',
                'type'        => 'integer',
                'description' => 'Plafond de retrait mensuel pour KYC niveau 3 (FCFA)',
                'is_public'   => true,
            ],
            [
                'key'         => 'otp_expires_minutes',
                'value'       => '10',
                'type'        => 'integer',
                'description' => 'Durée de validité d\'un code OTP (minutes)',
                'is_public'   => false,
            ],
            [
                'key'         => 'otp_max_attempts',
                'value'       => '3',
                'type'        => 'integer',
                'description' => 'Nombre maximum de tentatives de saisie d\'un OTP',
                'is_public'   => false,
            ],
        ];

        foreach ($configs as $config) {
            PlatformConfig::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }
}
