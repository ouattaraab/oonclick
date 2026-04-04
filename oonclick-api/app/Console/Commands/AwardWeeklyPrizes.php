<?php

namespace App\Console\Commands;

use App\Models\AdView;
use App\Models\FeatureSetting;
use App\Models\User;
use App\Modules\Payment\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Attribue les prix hebdomadaires du classement (leaderboard_enhanced).
 *
 * Lancé chaque lundi à 00:05, calcule le top 3 de la semaine précédente
 * par nombre de vues complétées et crédite les wallets avec les montants
 * définis dans la config de la feature 'leaderboard_enhanced'.
 */
class AwardWeeklyPrizes extends Command
{
    /**
     * Signature de la commande Artisan.
     *
     * @var string
     */
    protected $signature = 'oonclick:award-weekly-prizes';

    /**
     * Description de la commande.
     *
     * @var string
     */
    protected $description = 'Attribue les prix hebdomadaires aux top 3 du classement (leaderboard_enhanced).';

    public function __construct(
        private readonly WalletService $walletService,
    ) {
        parent::__construct();
    }

    /**
     * Exécute la commande.
     *
     * Vérifie que la feature est activée, récupère le top 3 de la semaine
     * précédente et crédite les wallets avec les montants configurés.
     */
    public function handle(): int
    {
        // S'assurer que la feature est activée
        if (! FeatureSetting::isEnabled('leaderboard_enhanced')) {
            $this->info('Feature leaderboard_enhanced désactivée — aucun prix distribué.');

            return self::SUCCESS;
        }

        $config       = FeatureSetting::getConfig('leaderboard_enhanced');
        $weeklyPrizes = $config['weekly_prizes'] ?? [];

        if (empty($weeklyPrizes)) {
            $this->info('Aucun prix hebdomadaire configuré — aucun prix distribué.');

            return self::SUCCESS;
        }

        // Semaine précédente (lundi à dimanche inclus)
        $startOfLastWeek = now()->subWeek()->startOfWeek();
        $endOfLastWeek   = now()->subWeek()->endOfWeek();

        $this->info("Calcul des prix pour la semaine du {$startOfLastWeek->toDateString()} au {$endOfLastWeek->toDateString()}...");

        // Récupérer le top N subscribers par vues complétées sur la semaine passée
        $topCount = count($weeklyPrizes);

        $topUsers = User::where('role', 'subscriber')
            ->where('is_active', true)
            ->withCount(['adViews as week_views' => function ($q) use ($startOfLastWeek, $endOfLastWeek) {
                $q->where('is_completed', true)
                  ->where('completed_at', '>=', $startOfLastWeek)
                  ->where('completed_at', '<=', $endOfLastWeek);
            }])
            ->orderByDesc('week_views')
            ->limit($topCount)
            ->get();

        $awarded = 0;

        foreach ($topUsers as $index => $winner) {
            // Ignorer les utilisateurs sans vues
            if ($winner->week_views <= 0) {
                continue;
            }

            $prizeConfig = $weeklyPrizes[$index] ?? null;

            if ($prizeConfig === null) {
                continue;
            }

            $rank   = $index + 1;
            $amount = (int) ($prizeConfig['amount'] ?? 0);

            if ($amount <= 0) {
                continue;
            }

            try {
                $this->walletService->credit(
                    userId: $winner->id,
                    amount: $amount,
                    type: 'bonus',
                    description: "Prix classement hebdomadaire — rang #{$rank} (semaine du {$startOfLastWeek->toDateString()})",
                    metadata: [
                        'rank'        => $rank,
                        'week_views'  => $winner->week_views,
                        'week_start'  => $startOfLastWeek->toDateString(),
                        'week_end'    => $endOfLastWeek->toDateString(),
                    ]
                );

                $awarded++;

                $this->info("Prix distribué : user#{$winner->id} (rang #{$rank}) — {$amount} FCFA pour {$winner->week_views} vue(s).");

                Log::info("AwardWeeklyPrizes : rang#{$rank} user#{$winner->id} crédité de {$amount} FCFA ({$winner->week_views} vues semaine).", [
                    'week_start' => $startOfLastWeek->toDateString(),
                    'week_end'   => $endOfLastWeek->toDateString(),
                ]);
            } catch (\Throwable $e) {
                $this->error("Erreur lors du crédit pour user#{$winner->id} : {$e->getMessage()}");
                Log::error("AwardWeeklyPrizes : erreur crédit user#{$winner->id}: {$e->getMessage()}");
            }
        }

        $this->info("{$awarded} prix distribué(s) avec succès.");

        return self::SUCCESS;
    }
}
