<?php

namespace App\Filament\Widgets;

use App\Models\AppInstall;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AppInstallsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $total   = AppInstall::count();
        $android = AppInstall::where('platform', 'android')->count();
        $ios     = AppInstall::where('platform', 'ios')->count();
        $today   = AppInstall::whereDate('first_seen_at', today())->count();

        return [
            Stat::make('Total installations', $total)
                ->icon('heroicon-o-device-phone-mobile')
                ->color('primary'),

            Stat::make('Android', $android)
                ->icon('heroicon-o-device-phone-mobile')
                ->color('success'),

            Stat::make('iOS', $ios)
                ->icon('heroicon-o-device-phone-mobile')
                ->color('info'),

            Stat::make('Aujourd\'hui', $today)
                ->icon('heroicon-o-calendar-days')
                ->color('warning'),
        ];
    }
}
