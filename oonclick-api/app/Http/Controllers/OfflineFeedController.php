<?php

namespace App\Http\Controllers;

use App\Models\AdView;
use App\Models\Campaign;
use App\Models\FeatureSetting;
use App\Modules\Diffusion\Services\MatchingService;
use App\Modules\Diffusion\Services\ViewTrackingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OfflineFeedController extends Controller
{
    public function __construct(
        private readonly MatchingService $matchingService,
        private readonly ViewTrackingService $viewTrackingService,
    ) {}

    /**
     * Pre-download eligible campaigns for offline viewing.
     *
     * GET /api/feed/preload
     */
    public function preload(Request $request): JsonResponse
    {
        if (! FeatureSetting::isEnabled('offline_mode')) {
            return response()->json(['message' => 'Offline mode is not available.'], 403);
        }

        $config           = FeatureSetting::getConfig('offline_mode');
        $maxCampaigns     = (int) ($config['max_preload_campaigns'] ?? 5);
        $validityHours    = (int) ($config['preload_validity_hours'] ?? 24);

        $subscriber = $request->user();
        $campaigns  = $this->matchingService->getEligibleCampaigns($subscriber)->take($maxCampaigns);

        $preloadedAt = now();
        $validUntil  = $preloadedAt->copy()->addHours($validityHours);

        $payload = $campaigns->map(fn (Campaign $campaign) => [
            'id'            => $campaign->id,
            'title'         => $campaign->title,
            'format'        => $campaign->format,
            'media_url'     => $campaign->media_url,
            'thumbnail_url' => $campaign->thumbnail_url,
            'duration'      => $campaign->duration_seconds,
            'quiz_data'     => $campaign->format === 'quiz' ? $campaign->quiz_data : null,
        ]);

        return response()->json([
            'preloaded_at' => $preloadedAt->toIso8601String(),
            'valid_until'  => $validUntil->toIso8601String(),
            'campaigns'    => $payload,
        ]);
    }

    /**
     * Sync completed offline views back to the server.
     *
     * POST /api/feed/sync
     *
     * Body: { "views": [{ "campaign_id", "started_at", "completed_at", "duration_watched" }] }
     */
    public function sync(Request $request): JsonResponse
    {
        if (! FeatureSetting::isEnabled('offline_mode')) {
            return response()->json(['message' => 'Offline mode is not available.'], 403);
        }

        $config       = FeatureSetting::getConfig('offline_mode');
        $maxBatchSize = (int) ($config['sync_max_batch_size'] ?? 10);

        $request->validate([
            'views'                       => ['required', 'array', "max:{$maxBatchSize}"],
            'views.*.campaign_id'         => ['required', 'integer'],
            'views.*.started_at'          => ['required', 'date'],
            'views.*.completed_at'        => ['required', 'date', 'after:views.*.started_at'],
            'views.*.duration_watched'    => ['required', 'integer', 'min:1'],
        ]);

        $subscriber    = $request->user();
        $synced        = 0;
        $creditsEarned = 0;
        $errors        = [];

        foreach ($request->input('views') as $index => $viewData) {
            $campaignId = (int) $viewData['campaign_id'];

            // Validate campaign exists
            $campaign = Campaign::find($campaignId);
            if (! $campaign) {
                $errors[] = ['index' => $index, 'campaign_id' => $campaignId, 'reason' => 'Campaign not found.'];
                continue;
            }

            // Check subscriber hasn't already completed this campaign
            $alreadyCompleted = AdView::where('campaign_id', $campaignId)
                ->where('subscriber_id', $subscriber->id)
                ->where('is_completed', true)
                ->exists();

            if ($alreadyCompleted) {
                $errors[] = ['index' => $index, 'campaign_id' => $campaignId, 'reason' => 'Campaign already viewed.'];
                continue;
            }

            try {
                // Record start of view using offline timestamps
                $adView = $this->viewTrackingService->startView($subscriber, $campaign);

                // Backfill the offline started_at timestamp
                $adView->started_at = Carbon::parse($viewData['started_at']);
                $adView->save();

                // Complete the view
                $result = $this->viewTrackingService->completeView($adView, (int) $viewData['duration_watched']);

                // Backfill the offline completed_at timestamp
                $adView->completed_at = Carbon::parse($viewData['completed_at']);
                $adView->save();

                if ($result['credited']) {
                    $creditsEarned += $result['amount'];
                }

                $synced++;
            } catch (\Throwable $e) {
                Log::warning("OfflineFeedController: sync failed for campaign #{$campaignId} subscriber #{$subscriber->id}: {$e->getMessage()}");
                $errors[] = ['index' => $index, 'campaign_id' => $campaignId, 'reason' => 'Processing error.'];
            }
        }

        return response()->json([
            'synced'         => $synced,
            'credits_earned' => $creditsEarned,
            'errors'         => $errors,
        ]);
    }
}
