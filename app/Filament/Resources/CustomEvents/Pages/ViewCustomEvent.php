<?php

namespace App\Filament\Resources\CustomEvents\Pages;

use App\Filament\Resources\CustomEvents\CustomEventResource;
use App\Filament\Widgets\CustomEventStatsOverview;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomEvent extends ViewRecord
{
    protected static string $resource = CustomEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CustomEventStatsOverview::class,
        ];
    }

    public function getWidgetData(): array
    {
        return [
            'record' => $this->record,
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager::class,
        ];
    }
}