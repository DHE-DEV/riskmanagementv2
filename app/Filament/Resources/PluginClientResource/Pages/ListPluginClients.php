<?php

namespace App\Filament\Resources\PluginClientResource\Pages;

use App\Filament\Resources\PluginClientResource;
use App\Filament\Resources\PluginClientResource\Widgets\PluginStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListPluginClients extends ListRecords
{
    protected static string $resource = PluginClientResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            PluginStatsWidget::class,
        ];
    }
}
