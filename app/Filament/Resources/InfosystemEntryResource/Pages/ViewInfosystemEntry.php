<?php

namespace App\Filament\Resources\InfosystemEntryResource\Pages;

use App\Filament\Resources\InfosystemEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInfosystemEntry extends ViewRecord
{
    protected static string $resource = InfosystemEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}