<?php

namespace App\Filament\Resources\CustomEvents\Pages;

use App\Filament\Resources\CustomEvents\CustomEventResource;
use App\Filament\Widgets\CustomEventStatsOverview;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomEvent extends ViewRecord
{
    protected static string $resource = CustomEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('trigger_notifications')
                ->label('Benachrichtigungen auslösen')
                ->icon('heroicon-o-bell-alert')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Benachrichtigungen auslösen')
                ->modalDescription('Möchten Sie die Benachrichtigungen für dieses Event erneut auslösen? Empfänger, die bereits benachrichtigt wurden, erhalten keine doppelte E-Mail.')
                ->modalSubmitActionLabel('Ja, Benachrichtigungen senden')
                ->action(function () {
                    $event = $this->record;

                    if (!$event->is_active || $event->review_status !== 'approved') {
                        \Filament\Notifications\Notification::make()
                            ->title('Benachrichtigung nicht möglich')
                            ->body('Das Event muss aktiv und freigegeben sein.')
                            ->danger()
                            ->send();
                        return;
                    }

                    \App\Jobs\SendTravelAlertNotifications::dispatch($event, force: true);
                    \App\Jobs\SendGtmNotifications::dispatch($event, force: true);

                    \Filament\Notifications\Notification::make()
                        ->title('Benachrichtigungen ausgelöst')
                        ->body('Travel Alert und GTM Benachrichtigungen werden im Hintergrund verarbeitet.')
                        ->success()
                        ->send();
                }),

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