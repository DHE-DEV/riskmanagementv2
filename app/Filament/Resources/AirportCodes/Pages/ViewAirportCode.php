<?php

namespace App\Filament\Resources\AirportCodes\Pages;

use App\Filament\Resources\AirportCodes\AirportCodeResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAirportCode extends ViewRecord
{
    protected static string $resource = AirportCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
