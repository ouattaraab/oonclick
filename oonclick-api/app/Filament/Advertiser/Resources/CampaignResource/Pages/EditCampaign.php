<?php

namespace App\Filament\Advertiser\Resources\CampaignResource\Pages;

use App\Filament\Advertiser\Resources\CampaignResource;
use Filament\Support\Enums\MaxWidth;
use Filament\Resources\Pages\EditRecord;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::FourExtraLarge;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
