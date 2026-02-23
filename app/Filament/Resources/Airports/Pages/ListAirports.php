<?php

namespace App\Filament\Resources\Airports\Pages;

use App\Filament\Resources\Airports\AirportResource;
use App\Filament\Widgets\AirportDataCompletenessChart;
use App\Filament\Widgets\AirportGrowthChart;
use App\Filament\Widgets\AirportStatsOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAirports extends ListRecords
{
    protected static string $resource = AirportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AirportStatsOverview::class,
            AirportGrowthChart::class,
            AirportDataCompletenessChart::class,
        ];
    }
}
