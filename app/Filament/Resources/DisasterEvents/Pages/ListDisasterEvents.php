<?php

namespace App\Filament\Resources\DisasterEvents\Pages;

use App\Filament\Resources\DisasterEvents\DisasterEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDisasterEvents extends ListRecords
{
    protected static string $resource = DisasterEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
