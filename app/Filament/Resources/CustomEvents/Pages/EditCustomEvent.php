<?php

namespace App\Filament\Resources\CustomEvents\Pages;

use App\Filament\Resources\CustomEvents\CustomEventResource;
use App\Filament\Widgets\CustomEventStatsOverview;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomEvent extends EditRecord
{
    protected static string $resource = CustomEventResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageCountries')
                ->label('Länder & Standorte verwalten')
                ->icon('heroicon-o-globe-alt')
                ->color('success')
                ->url(fn () => CustomEventResource::getUrl('manage-countries', ['record' => $this->record])),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
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
}
