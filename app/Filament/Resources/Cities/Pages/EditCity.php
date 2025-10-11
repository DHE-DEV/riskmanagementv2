<?php

namespace App\Filament\Resources\Cities\Pages;

use App\Filament\Resources\Cities\CityResource;
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

class EditCity extends EditRecord
{
    protected static string $resource = CityResource::class;

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
                                ->forModel('City')
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
                    $city = $this->record;

                    // Stadtdaten für Platzhalter vorbereiten
                    $cityData = [
                        'name' => $city->getName('de'),
                        'name_en' => $city->getName('en'),
                        'country' => $city->country?->getName('de') ?? 'N/A',
                        'country_en' => $city->country?->getName('en') ?? 'N/A',
                        'country_code' => $city->country?->iso_code ?? 'N/A',
                        'region' => $city->region?->getName('de') ?? 'N/A',
                        'region_en' => $city->region?->getName('en') ?? 'N/A',
                        'population' => $city->population ?? 'N/A',
                        'is_capital' => $city->is_capital ? 'Ja' : 'Nein',
                        'is_regional_capital' => $city->is_regional_capital ? 'Ja' : 'Nein',
                        'lat' => $city->lat ?? 'N/A',
                        'lng' => $city->lng ?? 'N/A',
                    ];

                    // ChatGPT Service verwenden
                    $chatGptService = app(ChatGptService::class);
                    $result = $chatGptService->processPrompt($prompt, $cityData);

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
