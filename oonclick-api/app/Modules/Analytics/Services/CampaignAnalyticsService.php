<?php

namespace App\Modules\Analytics\Services;

use App\Models\AdView;
use App\Models\Campaign;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CampaignAnalyticsService
{
    /**
     * Retourne les statistiques détaillées d'une campagne.
     * Le résultat est mis en cache 5 minutes.
     */
    public function getStats(int $campaignId): array
    {
        return Cache::remember("campaign_stats_{$campaignId}", 300, function () use ($campaignId) {
            $campaign = Campaign::with('escrow')->findOrFail($campaignId);

            $totalStarts = AdView::where('campaign_id', $campaignId)->count();

            $completedViews = AdView::where('campaign_id', $campaignId)
                ->where('is_completed', true)
                ->count();

            $creditedViews = AdView::where('campaign_id', $campaignId)
                ->where('is_completed', true)
                ->where('is_credited', true)
                ->count();

            $completionRate = $totalStarts > 0
                ? round(($completedViews / $totalStarts) * 100, 2)
                : 0;

            $creditRate = $completedViews > 0
                ? round(($creditedViews / $completedViews) * 100, 2)
                : 0;

            $avgWatchDuration = AdView::where('campaign_id', $campaignId)
                ->where('is_completed', true)
                ->avg('watch_duration_seconds') ?? 0;

            $viewsByHour = AdView::where('campaign_id', $campaignId)
                ->where('started_at', '>=', now()->subHours(24))
                ->select(DB::raw('HOUR(started_at) as hour'), DB::raw('COUNT(*) as count'))
                ->groupBy(DB::raw('HOUR(started_at)'))
                ->orderBy('hour')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->hour => $row->count])
                ->toArray();

            $viewsByGender = AdView::where('ad_views.campaign_id', $campaignId)
                ->join('subscriber_profiles', 'subscriber_profiles.user_id', '=', 'ad_views.subscriber_id')
                ->select('subscriber_profiles.gender', DB::raw('COUNT(*) as count'))
                ->groupBy('subscriber_profiles.gender')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->gender ?? 'inconnu' => $row->count])
                ->toArray();

            $viewsByCity = AdView::where('ad_views.campaign_id', $campaignId)
                ->join('subscriber_profiles', 'subscriber_profiles.user_id', '=', 'ad_views.subscriber_id')
                ->select('subscriber_profiles.city', DB::raw('COUNT(*) as count'))
                ->groupBy('subscriber_profiles.city')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->mapWithKeys(fn ($row) => [$row->city ?? 'inconnue' => $row->count])
                ->toArray();

            $viewsByOperator = AdView::where('ad_views.campaign_id', $campaignId)
                ->join('subscriber_profiles', 'subscriber_profiles.user_id', '=', 'ad_views.subscriber_id')
                ->select('subscriber_profiles.operator', DB::raw('COUNT(*) as count'))
                ->groupBy('subscriber_profiles.operator')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->operator ?? 'inconnu' => $row->count])
                ->toArray();

            $budgetUsed      = $campaign->views_count * $campaign->cost_per_view;
            $budgetRemaining = $campaign->budget - $budgetUsed;

            $escrow = $campaign->escrow;
            $escrowData = $escrow ? [
                'amount_locked'   => $escrow->amount_locked,
                'amount_released' => $escrow->amount_released,
                'remaining'       => $escrow->remaining,
            ] : [
                'amount_locked'   => 0,
                'amount_released' => 0,
                'remaining'       => 0,
            ];

            return [
                'campaign_id'        => $campaignId,
                'title'              => $campaign->title,
                'status'             => $campaign->status,
                'budget'             => $campaign->budget,
                'cost_per_view'      => $campaign->cost_per_view,
                'max_views'          => $campaign->max_views,
                'views_count'        => $campaign->views_count,
                'completion_rate'    => $completionRate,
                'credit_rate'        => $creditRate,
                'budget_used'        => $budgetUsed,
                'budget_remaining'   => $budgetRemaining,
                'avg_watch_duration' => round($avgWatchDuration, 2),
                'views_by_hour'      => $viewsByHour,
                'views_by_gender'    => $viewsByGender,
                'views_by_city'      => $viewsByCity,
                'views_by_operator'  => $viewsByOperator,
                'escrow'             => $escrowData,
            ];
        });
    }

    /**
     * Retourne une vue d'ensemble des campagnes d'un annonceur.
     */
    public function getAdvertiserOverview(int $advertiserId): array
    {
        $campaigns = Campaign::where('advertiser_id', $advertiserId)->get();
        $campaignIds = $campaigns->pluck('id')->toArray();

        $totalViews = AdView::whereIn('campaign_id', $campaignIds)
            ->where('is_completed', true)
            ->count();

        $totalSpent = $campaigns->sum(fn ($c) => $c->views_count * $c->cost_per_view);

        $campaignsSummary = $campaigns->map(function (Campaign $campaign) {
            $budgetUsed = $campaign->views_count * $campaign->cost_per_view;

            return [
                'id'              => $campaign->id,
                'title'           => $campaign->title,
                'status'          => $campaign->status,
                'format'          => $campaign->format,
                'budget'          => $campaign->budget,
                'budget_used'     => $budgetUsed,
                'budget_remaining'=> $campaign->budget - $budgetUsed,
                'views_count'     => $campaign->views_count,
                'max_views'       => $campaign->max_views,
                'starts_at'       => $campaign->starts_at?->toDateString(),
                'ends_at'         => $campaign->ends_at?->toDateString(),
            ];
        })->values()->toArray();

        return [
            'total_campaigns'  => $campaigns->count(),
            'active_campaigns' => $campaigns->where('status', 'active')->count(),
            'total_views'      => $totalViews,
            'total_spent'      => $totalSpent,
            'campaigns_summary'=> $campaignsSummary,
        ];
    }
}
