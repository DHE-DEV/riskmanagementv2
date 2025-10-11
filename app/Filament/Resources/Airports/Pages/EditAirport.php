<?php

namespace App\Filament\Resources\Airports\Pages;

use App\Filament\Resources\Airports\AirportResource;
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

class EditAirport extends EditRecord
{
    protected static string $resource = AirportResource::class;

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
                                ->forModel('Airport')
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
                    $airport = $this->record;

                    // Flughafendaten für Platzhalter vorbereiten
                    $airportData = [
                        'name' => $airport->name,
                        'iata_code' => $airport->iata_code ?? 'N/A',
                        'icao_code' => $airport->icao_code ?? 'N/A',
                        'city' => $airport->city?->getName('de') ?? 'N/A',
                        'city_en' => $airport->city?->getName('en') ?? 'N/A',
                        'country' => $airport->country?->getName('de') ?? 'N/A',
                        'country_en' => $airport->country?->getName('en') ?? 'N/A',
                        'country_code' => $airport->country?->iso_code ?? 'N/A',
                        'timezone' => $airport->timezone ?? 'N/A',
                        'dst_timezone' => $airport->dst_timezone ?? 'N/A',
                        'altitude' => $airport->altitude ?? 'N/A',
                        'type' => $airport->type ?? 'N/A',
                        'lat' => $airport->lat ?? 'N/A',
                        'lng' => $airport->lng ?? 'N/A',
                    ];

                    // ChatGPT Service verwenden
                    $chatGptService = app(ChatGptService::class);
                    $result = $chatGptService->processPrompt($prompt, $airportData);

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
