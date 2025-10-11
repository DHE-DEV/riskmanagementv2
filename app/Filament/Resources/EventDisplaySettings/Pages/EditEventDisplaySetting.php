<?php

namespace App\Filament\Resources\EventDisplaySettings\Pages;

use App\Filament\Resources\EventDisplaySettings\EventDisplaySettingResource;
use App\Models\EventDisplaySetting;
use Filament\Resources\Pages\EditRecord;

class EditEventDisplaySetting extends EditRecord
{
    protected static string $resource = EventDisplaySettingResource::class;

    // Singleton: Always load the first (and only) record
    public function mount(int | string $record = null): void
    {
        $this->record = EventDisplaySetting::current();

        $this->authorizeAccess();

        $this->fillForm();

        $this->previousUrl = url()->previous();
    }

    protected function getHeaderActions(): array
    {
        return [
            // No delete action for singleton
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Einstellungen gespeichert';
    }

    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title('Einstellungen gespeichert')
            ->body('Die Icon-Darstellung wurde aktualisiert. Bitte laden Sie die Karte neu, um die Änderungen zu sehen.')
            ->duration(5000);
    }
}
