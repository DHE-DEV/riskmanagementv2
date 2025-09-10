<?php

namespace App\Filament\Resources\EventCategories\Pages;

use App\Filament\Resources\EventCategories\EventCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEventCategory extends EditRecord
{
    protected static string $resource = EventCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
