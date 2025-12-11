<?php

namespace App\Filament\Resources\AirportCodes\Pages;

use App\Filament\Resources\AirportCodes\AirportCodeResource;
use Filament\Resources\Pages\ListRecords;

class ListAirportCodes extends ListRecords
{
    protected static string $resource = AirportCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
