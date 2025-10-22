<?php

namespace App\Filament\Resources\EntryConditionsLogs\Pages;

use App\Filament\Resources\EntryConditionsLogs\EntryConditionsLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEntryConditionsLog extends ViewRecord
{
    protected static string $resource = EntryConditionsLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
