<?php

namespace App\Filament\Resources\PlatformConfigResource\Pages;

use App\Filament\Resources\PlatformConfigResource;
use Filament\Resources\Pages\EditRecord;

class EditPlatformConfig extends EditRecord
{
    protected static string $resource = PlatformConfigResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
