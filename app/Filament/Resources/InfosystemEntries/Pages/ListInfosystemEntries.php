<?php

namespace App\Filament\Resources\InfosystemEntries\Pages;

use App\Filament\Resources\InfosystemEntries\InfosystemEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInfosystemEntries extends ListRecords
{
    protected static string $resource = InfosystemEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
