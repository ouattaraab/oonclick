<?php

namespace App\Observers;

use App\Models\Campaign;
use App\Services\AuditService;

class CampaignObserver
{
    public function created(Campaign $campaign): void
    {
        AuditService::log(
            action: 'campaign.created',
            userId: $campaign->advertiser_id,
            module: 'campaign',
            auditableType: Campaign::class,
            auditableId: $campaign->id,
            newValues: [
                'title'  => $campaign->title,
                'format' => $campaign->format,
                'budget' => $campaign->budget,
                'status' => $campaign->status,
            ],
        );
    }

    public function updated(Campaign $campaign): void
    {
        if ($campaign->wasChanged('status')) {
            AuditService::log(
                action: 'campaign.status_changed',
                userId: $campaign->advertiser_id,
                module: 'campaign',
                auditableType: Campaign::class,
                auditableId: $campaign->id,
                oldValues: ['status' => $campaign->getOriginal('status')],
                newValues: ['status' => $campaign->status],
            );
        }
    }
}
