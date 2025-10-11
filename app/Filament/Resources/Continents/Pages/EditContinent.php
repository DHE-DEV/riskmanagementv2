<?php

namespace App\Filament\Resources\Continents\Pages;

use App\Filament\Resources\Continents\ContinentResource;
use App\Models\AiPrompt;
use App\Services\ChatGptService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;

class EditContinent extends EditRecord
{
    protected static string $resource = ContinentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ai_assistant')
                ->label('KI-Assistent')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->form([
                    Select::make('prompt_id')
                        ->label('Aufgabe auswählen')
                        ->options(function () {
                            return AiPrompt::active()
                                ->forModel('Continent')
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

                    Placeholder::make('prompt_preview')
                        ->label('Prompt-Vorschau')
                        ->content(function ($get) {
                            if (!$get('prompt_id')) {
                                return 'Wählen Sie eine Aufgabe aus, um eine Vorschau zu sehen.';
                            }

                            $prompt = AiPrompt::find($get('prompt_id'));
                            if (!$prompt) {
                                return '';
                            }

                            return nl2br(e($prompt->prompt_template));
                        }),
                ])
                ->action(function (array $data) {
                    $prompt = AiPrompt::findOrFail($data['prompt_id']);
                    $continent = $this->record;

                    // Kontinentdaten für Platzhalter vorbereiten
                    $continentData = [
                        'name' => $continent->getName('de'),
                        'name_en' => $continent->getName('en'),
                        'code' => $continent->code,
                        'description' => $continent->description ?? 'N/A',
                        'countries_count' => $continent->countries()->count(),
                    ];

                    // ChatGPT Service verwenden
                    $chatGptService = app(ChatGptService::class);
                    $result = $chatGptService->processPrompt($prompt, $continentData);

                    // Ergebnis in Notification anzeigen mit HTML-Unterstützung
                    Notification::make()
                        ->title($prompt->name)
                        ->body(new HtmlString($result))
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
}
