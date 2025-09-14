<?php

namespace App\Filament\Resources\InfosystemEntries\Pages;

use App\Filament\Resources\InfosystemEntries\InfosystemEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInfosystemEntry extends EditRecord
{
    protected static string $resource = InfosystemEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
