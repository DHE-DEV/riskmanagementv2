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

    protected array $locationCountries = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing location assignments into the repeater format
        $record = $this->record->load(['countries', 'regions', 'cities']);

        $countriesData = [];
        foreach ($record->countries as $country) {
            $countryId = $country->id;

            // Find regions belonging to this country that are attached to this event
            $regionIds = $record->regions
                ->where('country_id', $countryId)
                ->pluck('id')
                ->toArray();

            // Find cities belonging to this country that are attached to this event
            $cityIds = $record->cities
                ->where('country_id', $countryId)
                ->pluck('id')
                ->toArray();

            $countriesData[] = [
                'country_id' => $countryId,
                'region_ids' => $regionIds,
                'city_ids' => $cityIds,
            ];
        }

        $data['location_countries'] = $countriesData;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->locationCountries = $data['location_countries'] ?? [];
        unset($data['location_countries']);

        return $data;
    }

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
            Action::make('ai_assistant')
                ->label('KI-Assistent')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Select::make('prompt_id')
                        ->label('Aufgabe auswählen')
                        ->options(function () {
                            return \App\Models\AiPrompt::active()
                                ->whereIn('model_type', ['CustomEvent', 'TextImprovement_Title', 'TextImprovement_Description'])
                                ->ordered()
                                ->get()
                                ->mapWithKeys(fn ($prompt) => [
                                    $prompt->id => $prompt->name . ($prompt->description ? ' - ' . $prompt->description : '')
                                ]);
                        })
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live(),

                    \Filament\Forms\Components\Placeholder::make('prompt_preview')
                        ->label('Prompt-Vorschau')
                        ->content(function ($get) {
                            if (!$get('prompt_id')) {
                                return 'Wählen Sie eine Aufgabe aus, um eine Vorschau zu sehen.';
                            }

                            $prompt = \App\Models\AiPrompt::find($get('prompt_id'));
                            if (!$prompt) {
                                return '';
                            }

                            return nl2br(e($prompt->prompt_template));
                        }),
                ])
                ->action(function (array $data) {
                    $prompt = \App\Models\AiPrompt::findOrFail($data['prompt_id']);
                    $event = $this->record;

                    // Event-Daten für Platzhalter vorbereiten
                    $eventData = [
                        'title' => $event->title,
                        'description' => strip_tags($event->popup_content ?? $event->description ?? ''),
                        'event_type' => $event->eventType?->name ?? 'N/A',
                        'event_types' => $event->eventTypes->pluck('name')->implode(', ') ?: 'N/A',
                        'priority' => $event->priority ?? 'N/A',
                        'severity' => $event->severity ?? 'N/A',
                        'start_date' => $event->start_date?->format('d.m.Y H:i') ?? 'N/A',
                        'end_date' => $event->end_date?->format('d.m.Y H:i') ?? 'N/A',
                        'is_active' => $event->is_active ? 'Ja' : 'Nein',
                        'archived' => $event->archived ? 'Ja' : 'Nein',
                        'countries' => $event->countries->map(fn($c) => $c->getName('de'))->implode(', ') ?: 'N/A',
                        'data_source' => $event->data_source ?? 'N/A',

                        // Für TextImprovement-Prompts
                        'text' => $event->title, // Standard: Titel als Text
                        'selected_event_types' => $event->eventTypes->pluck('name')->implode(', ') ?: 'Keine ausgewählt',
                        'available_event_types' => \App\Models\EventType::active()->ordered()->pluck('name')->implode(', '),
                    ];

                    // ChatGPT Service verwenden
                    $chatGptService = app(\App\Services\ChatGptService::class);
                    $result = $chatGptService->processPrompt($prompt, $eventData);

                    // Ergebnis in Notification anzeigen mit HTML-Unterstützung
                    \Filament\Notifications\Notification::make()
                        ->title($prompt->name)
                        ->body(new \Illuminate\Support\HtmlString($result))
                        ->success()
                        ->duration(null) // Bleibt offen bis manuell geschlossen
                        ->send();
                })
                ->modalWidth('3xl')
                ->modalSubmitActionLabel('KI ausführen')
                ->modalCancelActionLabel('Schließen'),

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

                    \App\Jobs\SendRiskEventNotifications::dispatch($event, force: true);

                    \Filament\Notifications\Notification::make()
                        ->title('Benachrichtigungen ausgelöst')
                        ->body('Die Benachrichtigungen werden im Hintergrund verarbeitet.')
                        ->success()
                        ->send();
                }),

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
        // Sync location data from repeater
        $this->syncLocationData();

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

    protected function syncLocationData(): void
    {
        $newCountryIds = [];
        $newRegionIds = [];
        $newCityIds = [];

        foreach ($this->locationCountries as $entry) {
            $countryId = $entry['country_id'] ?? null;
            if (!$countryId) {
                continue;
            }

            $newCountryIds[] = $countryId;

            foreach ($entry['region_ids'] ?? [] as $regionId) {
                $newRegionIds[] = $regionId;
            }

            foreach ($entry['city_ids'] ?? [] as $cityId) {
                $newCityIds[] = $cityId;
            }
        }

        // Get existing IDs
        $existingCountryIds = $this->record->countries()->pluck('countries.id')->toArray();
        $existingRegionIds = $this->record->regions()->pluck('regions.id')->toArray();
        $existingCityIds = $this->record->cities()->pluck('cities.id')->toArray();

        // Detach removed entries
        $countriesToDetach = array_diff($existingCountryIds, $newCountryIds);
        $regionsToDetach = array_diff($existingRegionIds, $newRegionIds);
        $citiesToDetach = array_diff($existingCityIds, $newCityIds);

        if (!empty($countriesToDetach)) {
            $this->record->countries()->detach($countriesToDetach);
        }
        if (!empty($regionsToDetach)) {
            $this->record->regions()->detach($regionsToDetach);
        }
        if (!empty($citiesToDetach)) {
            $this->record->cities()->detach($citiesToDetach);
        }

        // Attach new entries (existing pivot data stays untouched)
        $countriesToAttach = array_diff($newCountryIds, $existingCountryIds);
        $regionsToAttach = array_diff($newRegionIds, $existingRegionIds);
        $citiesToAttach = array_diff($newCityIds, $existingCityIds);

        foreach ($countriesToAttach as $id) {
            $this->record->countries()->attach($id, ['use_default_coordinates' => true]);
        }
        foreach ($regionsToAttach as $id) {
            $this->record->regions()->attach($id, ['use_default_coordinates' => true]);
        }
        foreach ($citiesToAttach as $id) {
            $this->record->cities()->attach($id, ['use_default_coordinates' => true]);
        }
    }
}
