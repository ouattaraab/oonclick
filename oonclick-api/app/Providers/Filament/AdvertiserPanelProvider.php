<?php

namespace App\Providers\Filament;

use App\Filament\Advertiser\Resources\CampaignResource;
use App\Filament\Advertiser\Widgets\AdvertiserCampaignsWidget;
use App\Filament\Advertiser\Widgets\AdvertiserStatsWidget;
use App\Filament\Advertiser\Widgets\AdvertiserViewsChartWidget;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdvertiserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('advertiser')
            ->path('advertiser')
            ->brandName('oon.click')
            ->login()
            ->colors([
                'primary' => Color::hex('#2AABF0'),
                'gray'    => Color::hex('#5A7098'),
                'info'    => Color::hex('#1A95D8'),
                'success' => Color::hex('#16A34A'),
                'warning' => Color::hex('#D97706'),
                'danger'  => Color::hex('#DC2626'),
            ])
            ->font('Nunito')
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/advertiser/theme.css')

            // ----------------------------------------------------------------
            // Auth guard + access restriction (annonceurs seulement)
            // ----------------------------------------------------------------
            ->authGuard('web')
            ->authMiddleware([
                Authenticate::class,
            ])

            // ----------------------------------------------------------------
            // Pages & Widgets du dashboard
            // ----------------------------------------------------------------
            ->discoverPages(
                in: app_path('Filament/Advertiser/Pages'),
                for: 'App\\Filament\\Advertiser\\Pages'
            )
            ->resources([
                CampaignResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                AdvertiserStatsWidget::class,
                AdvertiserViewsChartWidget::class,
                AdvertiserCampaignsWidget::class,
            ])

            // ----------------------------------------------------------------
            // Middleware
            // ----------------------------------------------------------------
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}
