<?php

namespace App\Filament\Resources\InfosystemEntryResource\Pages;

use App\Filament\Resources\InfosystemEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInfosystemEntry extends EditRecord
{
    protected static string $resource = InfosystemEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}