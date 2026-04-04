<?php

namespace App\Filament\Advertiser\Resources\CampaignResource\Pages;

use App\Filament\Advertiser\Resources\CampaignResource;
use Filament\Support\Enums\MaxWidth;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::FourExtraLarge;
    }

    /**
     * Automatically set the advertiser_id and initial status before saving.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['advertiser_id'] = auth()->id();
        $data['status']        = 'pending_review';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
