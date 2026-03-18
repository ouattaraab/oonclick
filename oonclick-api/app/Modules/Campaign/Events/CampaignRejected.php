<?php

namespace App\Modules\Campaign\Events;

use App\Models\Campaign;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignRejected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("advertiser.{$this->campaign->advertiser_id}");
    }

    public function broadcastAs(): string
    {
        return 'campaign.rejected';
    }

    public function broadcastWith(): array
    {
        return [
            'campaign_id'      => $this->campaign->id,
            'title'            => $this->campaign->title,
            'rejection_reason' => $this->campaign->rejection_reason,
        ];
    }
}
