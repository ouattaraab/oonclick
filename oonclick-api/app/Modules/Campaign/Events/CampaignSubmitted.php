<?php

namespace App\Modules\Campaign\Events;

use App\Models\Campaign;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('admin');
    }

    public function broadcastAs(): string
    {
        return 'campaign.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'campaign_id'   => $this->campaign->id,
            'title'         => $this->campaign->title,
            'format'        => $this->campaign->format,
            'advertiser_id' => $this->campaign->advertiser_id,
            'submitted_at'  => now()->toIso8601String(),
        ];
    }
}
