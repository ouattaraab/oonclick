<?php

namespace App\Console\Commands;

use App\Models\AdView;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendDailyStats extends Command
{
    /**
     * Nom et signature de la commande Artisan.
     *
     * @var string
     */
    protected $signature = 'oonclick:daily-stats';

    /**
     * Description de la commande.
     *
     * @var string
     */
    protected $description = 'Envoie un résumé quotidien des statistiques aux annonceurs ayant des campagnes actives.';

    /**
     * Exécute la commande.
     *
     * Pour chaque annonceur avec au moins une campagne active,
     * calcule les vues du jour et envoie une notification database
     * si le total de vues est supérieur à zéro.
     */
    public function handle(): int
    {
        $notifiedCount = 0;

        // Récupérer les annonceurs ayant au moins une campagne active
        $advertisers = User::where('role', 'advertiser')
            ->whereHas('campaigns', fn ($q) => $q->where('status', 'active'))
            ->with(['campaigns' => fn ($q) => $q->where('status', 'active')])
            ->get();

        foreach ($advertisers as $advertiser) {
            $campaignIds = $advertiser->campaigns->pluck('id');

            // Calculer les vues complétées du jour sur toutes les campagnes actives
            $viewsToday = AdView::whereIn('campaign_id', $campaignIds)
                ->whereDate('completed_at', today())
                ->where('is_completed', true)
                ->count();

            if ($viewsToday <= 0) {
                continue;
            }

            // Calculer les dépenses du jour (vues × coût unitaire par campagne)
            $revenueToday = (int) AdView::whereIn('ad_views.campaign_id', $campaignIds)
                ->whereDate('ad_views.completed_at', today())
                ->where('ad_views.is_completed', true)
                ->join('campaigns', 'ad_views.campaign_id', '=', 'campaigns.id')
                ->sum('campaigns.cost_per_view');

            // Insérer directement en base — canal `database` sans classe Notification dédiée
            DB::table('notifications')->insert([
                'id'              => Str::uuid()->toString(),
                'type'            => 'daily_stats',
                'notifiable_type' => User::class,
                'notifiable_id'   => $advertiser->id,
                'data'            => json_encode([
                    'title'          => 'Résumé du jour',
                    'body'           => "Vos campagnes ont enregistré {$viewsToday} vue(s) aujourd'hui pour un total de {$revenueToday} FCFA dépensés.",
                    'views_today'    => $viewsToday,
                    'revenue_today'  => $revenueToday,
                    'campaign_count' => $advertiser->campaigns->count(),
                    'date'           => today()->toDateString(),
                ]),
                'read_at'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $notifiedCount++;
        }

        Log::info("oonclick:daily-stats — {$notifiedCount} annonceur(s) notifié(s).", [
            'date' => today()->toDateString(),
        ]);

        $this->info("{$notifiedCount} annonceur(s) notifié(s) avec succès.");

        return self::SUCCESS;
    }
}
