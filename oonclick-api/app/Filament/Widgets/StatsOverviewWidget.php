<?php

namespace App\Filament\Widgets;

use App\Models\AdView;
use App\Models\Campaign;
use App\Models\User;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    // =========================================================================
    // Stats
    // =========================================================================

    protected function getStats(): array
    {
        // Abonnés actifs
        $activeSubscribers     = User::subscribers()->where('is_active', true)->where('is_suspended', false)->count();
        $subscribersLastWeek   = User::subscribers()
            ->where('is_active', true)
            ->where('is_suspended', false)
            ->where('created_at', '<', now()->subWeek())
            ->count();
        $subscribersDelta      = $activeSubscribers - $subscribersLastWeek;
        $subscribersDeltaLabel = ($subscribersDelta >= 0 ? '+' : '') . $subscribersDelta . ' cette semaine';

        // Campagnes actives
        $activeCampaigns     = Campaign::where('status', 'active')->count();
        $pendingCampaigns    = Campaign::where('status', 'pending_review')->count();
        $campaignsDeltaLabel = "+{$pendingCampaigns} en attente";

        // Vues cette semaine
        $viewsThisWeek     = AdView::where('is_completed', true)
            ->where('started_at', '>=', now()->startOfWeek())
            ->count();
        $viewsLastWeek     = AdView::where('is_completed', true)
            ->whereBetween('started_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->count();
        $viewsPct          = $viewsLastWeek > 0
            ? round((($viewsThisWeek - $viewsLastWeek) / $viewsLastWeek) * 100)
            : 0;
        $viewsDeltaLabel   = ($viewsPct >= 0 ? '+' : '') . $viewsPct . '% vs semaine passée';

        // Revenus ce mois
        $revenueMonth      = AdView::where('is_credited', true)
            ->where('credited_at', '>=', now()->startOfMonth())
            ->sum('amount_credited');
        $revenueLastMonth  = AdView::where('is_credited', true)
            ->whereBetween('credited_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('amount_credited');
        $revenuePct        = $revenueLastMonth > 0
            ? round((($revenueMonth - $revenueLastMonth) / $revenueLastMonth) * 100)
            : 0;
        $revenueDeltaLabel = ($revenuePct >= 0 ? '+' : '') . $revenuePct . '% vs mois passé';
        $revenueFormatted  = $this->formatFcfa($revenueMonth);

        return [
            Stat::make('Abonnés actifs', number_format($activeSubscribers, 0, ',', ' '))
                ->description($subscribersDeltaLabel)
                ->descriptionIcon($subscribersDelta >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($subscribersDelta >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-users'),

            Stat::make('Campagnes actives', $activeCampaigns)
                ->description($campaignsDeltaLabel)
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary')
                ->icon('heroicon-o-megaphone'),

            Stat::make('Vues cette semaine', $this->formatNumber($viewsThisWeek))
                ->description($viewsDeltaLabel)
                ->descriptionIcon($viewsPct >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($viewsPct >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-eye'),

            Stat::make('Revenus FCFA', $revenueFormatted)
                ->description($revenueDeltaLabel)
                ->descriptionIcon($revenuePct >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenuePct >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-currency-dollar'),
        ];
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function formatFcfa(int $amount): string
    {
        if ($amount >= 1_000_000) {
            return number_format($amount / 1_000_000, 1, ',', '') . 'M FCFA';
        }
        if ($amount >= 1_000) {
            return number_format($amount / 1_000, 0, ',', '') . 'k FCFA';
        }
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    }

    private function formatNumber(int $num): string
    {
        if ($num >= 1_000_000) {
            return number_format($num / 1_000_000, 1, ',', '') . 'M';
        }
        if ($num >= 1_000) {
            return number_format($num / 1_000, 0, ',', '') . 'k';
        }
        return (string) $num;
    }
}
