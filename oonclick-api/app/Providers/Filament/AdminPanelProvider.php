<?php

namespace App\Providers\Filament;

use App\Filament\Resources\AppVersionResource;
use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\CampaignResource;
use App\Filament\Resources\FraudEventResource;
use App\Filament\Resources\PlatformConfigResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\WithdrawalResource;
use App\Filament\Widgets\AppInstallsWidget;
use App\Filament\Widgets\FraudAlertsWidget;
use App\Filament\Widgets\PendingWithdrawalsWidget;
use App\Filament\Widgets\RecentCampaignsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\ViewsChartWidget;
use App\Models\FraudEvent;
use App\Models\Withdrawal;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
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
            ->font('Inter')
            ->sidebarCollapsibleOnDesktop()
            ->authGuard('web')
            ->viteTheme('resources/css/filament/admin/theme.css')

            // ----------------------------------------------------------------
            // Navigation groups
            // ----------------------------------------------------------------
            ->navigationGroups([
                NavigationGroup::make('Principal')
                    ->collapsible(false),
                NavigationGroup::make('Outils')
                    ->collapsible(false),
            ])

            // ----------------------------------------------------------------
            // Resources
            // ----------------------------------------------------------------
            ->resources([
                UserResource::class,
                CampaignResource::class,
                WithdrawalResource::class,
                FraudEventResource::class,
                PlatformConfigResource::class,
                AuditLogResource::class,
                AppVersionResource::class,
                RoleResource::class,
            ])

            // ----------------------------------------------------------------
            // Pages
            // ----------------------------------------------------------------
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])

            // ----------------------------------------------------------------
            // Dashboard widgets (order matters)
            // ----------------------------------------------------------------
            ->widgets([
                AppInstallsWidget::class,
                StatsOverviewWidget::class,
                ViewsChartWidget::class,
                RecentCampaignsWidget::class,
                PendingWithdrawalsWidget::class,
                FraudAlertsWidget::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
