<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\AdView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdvertiserStatsController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $campaignIds = Campaign::where('advertiser_id', $user->id)->pluck('id');

        // Global stats
        $totalViews    = AdView::whereIn('campaign_id', $campaignIds)->count();
        $totalBudget   = Campaign::where('advertiser_id', $user->id)->sum('budget');
        $totalSpent    = Campaign::where('advertiser_id', $user->id)->sum(DB::raw('views_count * cost_per_view'));
        $totalCampaigns  = Campaign::where('advertiser_id', $user->id)->count();
        $activeCampaigns = Campaign::where('advertiser_id', $user->id)->where('status', 'active')->count();

        // Views per day (last 30 days)
        $dailyViews = AdView::whereIn('campaign_id', $campaignIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Fill missing dates
        $chartLabels = [];
        $chartData   = [];
        for ($i = 29; $i >= 0; $i--) {
            $date          = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('d/m');
            $chartData[]   = $dailyViews[$date] ?? 0;
        }

        // Per-campaign breakdown
        $campaigns = Campaign::where('advertiser_id', $user->id)
            ->select('id', 'title', 'format', 'status', 'budget', 'cost_per_view', 'max_views', 'views_count')
            ->latest()
            ->get()
            ->map(function ($c) {
                $c->spent      = $c->views_count * $c->cost_per_view;
                $c->completion = $c->max_views > 0 ? round(($c->views_count / $c->max_views) * 100) : 0;
                return $c;
            });

        $walletBalance = $user->wallet?->balance ?? 0;

        return view('panel.advertiser.stats', compact(
            'totalViews', 'totalBudget', 'totalSpent', 'totalCampaigns', 'activeCampaigns',
            'chartLabels', 'chartData', 'campaigns', 'walletBalance'
        ));
    }
}
