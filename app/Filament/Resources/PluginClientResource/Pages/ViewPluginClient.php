<?php

namespace App\Filament\Resources\PluginClientResource\Pages;

use App\Filament\Resources\PluginClientResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPluginClient extends ViewRecord
{
    protected static string $resource = PluginClientResource::class;

    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\PluginClientResource\RelationManagers\DomainsRelationManager::class,
            \App\Filament\Resources\PluginClientResource\RelationManagers\UsageEventsRelationManager::class,
        ];
    }
}
