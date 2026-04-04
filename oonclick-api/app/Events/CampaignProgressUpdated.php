<?php

namespace App\Events;

use App\Models\Campaign;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $campaignId;
    public int $viewsCount;
    public int $maxViews;
    public int $budgetUsed;
    public int $budget;
    public int $remainingViews;
    public string $status;

    public function __construct(Campaign $campaign)
    {
        $this->campaignId    = $campaign->id;
        $this->viewsCount    = $campaign->views_count;
        $this->maxViews      = $campaign->max_views;
        $this->budgetUsed    = $campaign->views_count * $campaign->cost_per_view;
        $this->budget        = $campaign->budget;
        $this->remainingViews = max(0, $campaign->max_views - $campaign->views_count);
        $this->status        = $campaign->status;
    }

    public function broadcastOn(): array
    {
        return [new Channel('campaign.' . $this->campaignId)];
    }

    public function broadcastAs(): string
    {
        return 'progress.updated';
    }
}
