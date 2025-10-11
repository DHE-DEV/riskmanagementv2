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
