<?php

namespace App\Filament\Advertiser\Resources\CampaignResource\Pages;

use App\Filament\Advertiser\Resources\CampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nouvelle campagne'),
        ];
    }
}
