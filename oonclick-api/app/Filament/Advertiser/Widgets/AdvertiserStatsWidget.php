<?php

namespace App\Filament\Advertiser\Widgets;

use App\Models\AdView;
use App\Models\Campaign;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdvertiserStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    // =========================================================================

    protected function getStats(): array
    {
        $advertiser = auth()->user();

        if (! $advertiser) {
            return [];
        }

        $advertiserId = $advertiser->id;

        // Vues totales de toutes les campagnes de l'annonceur
        $totalViews = AdView::whereHas('campaign', fn ($q) => $q->where('advertiser_id', $advertiserId))
            ->where('is_completed', true)
            ->count();

        // Taux de complétion moyen
        $totalStarted   = AdView::whereHas('campaign', fn ($q) => $q->where('advertiser_id', $advertiserId))->count();
        $completionRate = $totalStarted > 0
            ? round(($totalViews / $totalStarted) * 100, 1)
            : 0;

        // FCFA dépensés au total
        $totalSpent = AdView::whereHas('campaign', fn ($q) => $q->where('advertiser_id', $advertiserId))
            ->where('is_credited', true)
            ->sum('amount_credited');

        // Campagnes actives
        $activeCampaigns  = Campaign::where('advertiser_id', $advertiserId)->where('status', 'active')->count();
        $pendingCampaigns = Campaign::where('advertiser_id', $advertiserId)->where('status', 'pending_review')->count();

        return [
            Stat::make('Vues totales', number_format($totalViews, 0, ',', ' '))
                ->description('+12% vs mois dernier')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->icon('heroicon-o-eye'),

            Stat::make('Taux de complétion', $completionRate . '%')
                ->description('+1.8 pts ce mois')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->icon('heroicon-o-check-badge'),

            Stat::make('FCFA dépensés', number_format($totalSpent, 0, ',', ' ') . ' FCFA')
                ->description('Budget total consommé')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Campagnes actives', $activeCampaigns)
                ->description($pendingCampaigns . ' en attente de validation')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info')
                ->icon('heroicon-o-megaphone'),
        ];
    }
}
