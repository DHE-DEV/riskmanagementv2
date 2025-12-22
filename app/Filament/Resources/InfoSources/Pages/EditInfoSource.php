<?php

namespace App\Filament\Resources\InfoSources\Pages;

use App\Filament\Resources\InfoSources\InfoSourceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInfoSource extends EditRecord
{
    protected static string $resource = InfoSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
