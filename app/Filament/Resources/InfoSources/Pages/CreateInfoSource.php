<?php

namespace App\Filament\Resources\InfoSources\Pages;

use App\Filament\Resources\InfoSources\InfoSourceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInfoSource extends CreateRecord
{
    protected static string $resource = InfoSourceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
