<?php

namespace App\Filament\Resources\InfoSources\Pages;

use App\Filament\Resources\InfoSources\InfoSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInfoSources extends ListRecords
{
    protected static string $resource = InfoSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Datenquelle hinzufügen')
                ->icon('heroicon-o-plus'),
        ];
    }
}
