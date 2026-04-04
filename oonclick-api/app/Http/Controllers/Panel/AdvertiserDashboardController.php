<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AdView;
use App\Models\Campaign;
use App\Models\Wallet;

class AdvertiserDashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();

        $campaigns     = Campaign::where('advertiser_id', $user->id)->latest()->limit(5)->get();
        $totalCampaigns = Campaign::where('advertiser_id', $user->id)->count();
        $pendingCampaigns = Campaign::where('advertiser_id', $user->id)->where('status', 'pending_review')->count();

        $campaignIds = Campaign::where('advertiser_id', $user->id)->pluck('id');

        $totalViews    = AdView::whereIn('campaign_id', $campaignIds)->where('is_completed', true)->count();
        $totalStarted  = AdView::whereIn('campaign_id', $campaignIds)->count();
        $completionRate = $totalStarted > 0 ? round($totalViews / $totalStarted * 100) : 0;
        $completionDelta = 0;
        $viewsPct = 0;

        $advertiserCampaigns = Campaign::where('advertiser_id', $user->id)->get();
        $totalSpent    = $advertiserCampaigns->sum(fn ($c) => $c->views_count * $c->cost_per_view);
        $totalBudget   = $advertiserCampaigns->sum('budget');
        $spentFormatted = $this->formatFcfa((int) $totalSpent);
        $budgetFormatted = $this->formatFcfa((int) $totalBudget);

        $wallet = Wallet::where('user_id', $user->id)->first();
        $walletBalance = $wallet?->balance ?? 0;

        $chartLabels = [];
        $chartData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $chartLabels[] = $day->translatedFormat('D d');
            $chartData[]   = AdView::whereIn('campaign_id', $campaignIds)->where('is_completed', true)->whereDate('started_at', $day->toDateString())->count();
        }

        return view('panel.advertiser.dashboard', compact(
            'campaigns', 'totalCampaigns', 'pendingCampaigns',
            'totalViews', 'viewsPct', 'completionRate', 'completionDelta',
            'spentFormatted', 'budgetFormatted', 'walletBalance',
            'chartLabels', 'chartData',
        ));
    }

    private function formatFcfa(int $amount): string
    {
        if ($amount >= 1_000_000) return number_format($amount / 1_000_000, 1, ',', '') . 'M';
        if ($amount >= 1_000) return number_format($amount / 1_000, 0, ',', '') . 'k';
        return number_format($amount, 0, ',', ' ');
    }
}
