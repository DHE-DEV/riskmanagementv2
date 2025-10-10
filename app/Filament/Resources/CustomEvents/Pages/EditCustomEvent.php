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
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getSaveAndListFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSaveAndListFormAction(): Action
    {
        return Action::make('saveAndList')
            ->label('Save & List')
            ->action('saveAndList')
            ->keyBindings(['mod+shift+s']);
    }

    public function saveAndList(): void
    {
        $this->save();
        $this->redirect($this->getResource()::getUrl('index'));
    }

    protected function getHeaderActions(): array
    {
        return [
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

    /**
     * Hook that runs after the record is saved
     * Updates marker_icon and event_type_id from the eventTypes relationship
     */
    protected function afterSave(): void
    {
        // Refresh the record to load the updated eventTypes relationship
        $this->record->refresh();
        $this->record->load('eventTypes');

        // Update marker_icon from the first EventType
        if ($this->record->eventTypes->isNotEmpty()) {
            $firstEventType = $this->record->eventTypes->first();

            $this->record->updateQuietly([
                'marker_icon' => $firstEventType->icon,
                'event_type_id' => $firstEventType->id,
            ]);
        }
    }
}
