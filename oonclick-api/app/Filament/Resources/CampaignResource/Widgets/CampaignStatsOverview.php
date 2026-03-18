<?php

namespace App\Filament\Resources\CampaignResource\Widgets;

use App\Models\Campaign;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampaignStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalBudget = Campaign::sum('budget');

        return [
            Stat::make('Total campagnes', Campaign::count())
                ->description('Toutes campagnes confondues')
                ->color('primary'),

            Stat::make('En attente de validation', Campaign::pendingReview()->count())
                ->description('À modérer')
                ->color('warning'),

            Stat::make('Campagnes actives', Campaign::active()->count())
                ->description('En diffusion actuellement')
                ->color('success'),

            Stat::make('Budget total engagé', number_format($totalBudget, 0, ',', ' ') . ' FCFA')
                ->description('Somme de tous les budgets')
                ->color('info'),
        ];
    }
}
