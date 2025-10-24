<?php

namespace App\Filament\Resources\EntryConditionsLogs\Pages;

use App\Filament\Resources\EntryConditionsLogs\EntryConditionsLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntryConditionsLogs extends ListRecords
{
    protected static string $resource = EntryConditionsLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
