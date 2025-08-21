<?php

namespace App\Filament\Resources\DisasterEvents\Pages;

use App\Filament\Resources\DisasterEvents\DisasterEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDisasterEvent extends EditRecord
{
    protected static string $resource = DisasterEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
