<?php

namespace App\Filament\Resources\InfoSourceItems\Pages;

use App\Filament\Resources\InfoSourceItems\InfoSourceItemResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewInfoSourceItem extends ViewRecord
{
    protected static string $resource = InfoSourceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_link')
                ->label('Quelle öffnen')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $this->record->link, shouldOpenInNewTab: true)
                ->visible(fn () => !empty($this->record->link)),

            Action::make('mark_reviewed')
                ->label('Als geprüft markieren')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'new')
                ->action(function () {
                    $this->record->markAsReviewed();
                }),

            Action::make('ignore')
                ->label('Ignorieren')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->visible(fn () => in_array($this->record->status, ['new', 'reviewed']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->markAsIgnored();
                }),
        ];
    }
}
