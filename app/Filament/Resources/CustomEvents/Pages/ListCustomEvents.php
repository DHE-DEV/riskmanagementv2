<?php

namespace App\Filament\Resources\CustomEvents\Pages;

use App\Filament\Resources\CustomEvents\CustomEventResource;
use App\Filament\Widgets\CustomEventsStatsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomEvents extends ListRecords
{
    protected static string $resource = CustomEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CustomEventsStatsOverview::class,
        ];
    }
}
