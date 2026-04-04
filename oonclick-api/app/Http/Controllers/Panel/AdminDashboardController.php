<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AdView;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\User;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function __invoke()
    {
        // --- KPI: Abonnés actifs ---
        $activeSubscribers   = User::where('role', 'subscriber')->where('is_active', true)->where('is_suspended', false)->count();
        $subscribersLastWeek = User::where('role', 'subscriber')->where('is_active', true)->where('is_suspended', false)->where('created_at', '<', now()->subWeek())->count();
        $subscribersDelta    = $activeSubscribers - $subscribersLastWeek;

        // --- KPI: Campagnes ---
        $activeCampaigns  = Campaign::where('status', 'active')->count();
        $pendingCampaigns = Campaign::where('status', 'pending_review')->count();

        // --- KPI: Vues ---
        $viewsThisWeek = AdView::where('is_completed', true)->where('started_at', '>=', now()->startOfWeek())->count();
        $viewsLastWeek = AdView::where('is_completed', true)->whereBetween('started_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count();
        $viewsPct      = $viewsLastWeek > 0 ? round(($viewsThisWeek - $viewsLastWeek) / $viewsLastWeek * 100) : 0;

        // --- KPI: Revenus ---
        $revenueMonth     = AdView::where('is_credited', true)->where('credited_at', '>=', now()->startOfMonth())->sum('amount_credited');
        $revenueLastMonth = AdView::where('is_credited', true)->whereBetween('credited_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->sum('amount_credited');
        $revenuePct       = $revenueLastMonth > 0 ? round(($revenueMonth - $revenueLastMonth) / $revenueLastMonth * 100) : 0;
        $revenueFormatted = $this->formatFcfa($revenueMonth);

        // --- Recent campaigns ---
        $recentCampaigns = Campaign::with('advertiser')->latest()->limit(5)->get();

        // --- Activity feed ---
        $recentActivity = $this->getRecentActivity();

        // --- Chart data: 7 derniers jours ---
        $chartLabels = [];
        $chartData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $day          = now()->subDays($i);
            $chartLabels[] = $day->translatedFormat('D d');
            $chartData[]   = AdView::where('is_completed', true)->whereDate('started_at', $day->toDateString())->count();
        }

        return view('panel.admin.dashboard', compact(
            'activeSubscribers', 'subscribersDelta',
            'activeCampaigns', 'pendingCampaigns',
            'viewsThisWeek', 'viewsPct',
            'revenueFormatted', 'revenuePct',
            'recentCampaigns', 'recentActivity',
            'chartLabels', 'chartData',
        ));
    }

    private function getRecentActivity(): array
    {
        $logs = AuditLog::with('user')->latest('created_at')->limit(8)->get();
        $items = [];

        foreach ($logs as $log) {
            $userName = $log->user?->name ?? $log->user?->phone ?? 'Utilisateur';

            $color = match ($log->action) {
                'user.registered'      => 'blue',
                'campaign.created'     => 'green',
                'withdrawal.requested' => 'amber',
                default                => 'blue',
            };

            $text = match ($log->action) {
                'user.registered'      => "{$userName} s'est inscrit",
                'campaign.created'     => "{$userName} a créé une campagne",
                'withdrawal.requested' => "Retrait demandé par {$userName}",
                default                => "{$log->action} — {$userName}",
            };

            $items[] = [
                'color' => $color,
                'text'  => $text,
                'time'  => $log->created_at->diffForHumans(),
            ];
        }

        return $items;
    }

    private function formatFcfa(int $amount): string
    {
        if ($amount >= 1_000_000) return number_format($amount / 1_000_000, 1, ',', '') . 'M FCFA';
        if ($amount >= 1_000)     return number_format($amount / 1_000, 0, ',', '') . 'k FCFA';
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    }
}
