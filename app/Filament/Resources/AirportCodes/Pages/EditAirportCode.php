<?php

namespace App\Filament\Resources\AirportCodes\Pages;

use App\Filament\Resources\AirportCodes\AirportCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAirportCode extends EditRecord
{
    protected static string $resource = AirportCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
