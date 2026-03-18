<?php

namespace App\Filament\Widgets;

use App\Models\AdView;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Withdrawal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $revenueToday = AdView::whereDate('credited_at', today())
            ->where('is_credited', true)
            ->sum('amount_credited');

        return [
            Stat::make('Abonnés', User::subscribers()->count())
                ->description('Total des abonnés inscrits')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Annonceurs', User::advertisers()->count())
                ->description('Total des annonceurs inscrits')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('success'),

            Stat::make('Campagnes actives', Campaign::where('status', 'active')->count())
                ->description('En diffusion actuellement')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('warning'),

            Stat::make('Vues aujourd\'hui', AdView::whereDate('started_at', today())->where('is_completed', true)->count())
                ->description('Vues complétées aujourd\'hui')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info'),

            Stat::make('CA aujourd\'hui', number_format($revenueToday, 0, ',', ' ') . ' FCFA')
                ->description('Revenus crédités aujourd\'hui')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Retraits en attente', Withdrawal::where('status', 'pending')->count())
                ->description('Demandes à traiter')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),
        ];
    }
}
