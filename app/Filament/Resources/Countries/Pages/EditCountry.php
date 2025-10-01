<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Resources\Countries\CountryResource;
use App\Models\AiPrompt;
use App\Services\ChatGptService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCountry extends EditRecord
{
    protected static string $resource = CountryResource::class;

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
                                ->forModel('Country')
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
                    $country = $this->record;

                    // Länderdaten für Platzhalter vorbereiten
                    $countryData = [
                        'name' => $country->getName('de'),
                        'name_en' => $country->getName('en'),
                        'iso_code' => $country->iso_code,
                        'iso3_code' => $country->iso3_code,
                        'continent' => $country->continent?->getName('de') ?? 'N/A',
                        'is_eu_member' => $country->is_eu_member ? 'Ja' : 'Nein',
                        'is_schengen_member' => $country->is_schengen_member ? 'Ja' : 'Nein',
                        'currency_code' => $country->currency_code ?? 'N/A',
                        'currency_name' => $country->currency_name ?? 'N/A',
                        'phone_prefix' => $country->phone_prefix ?? 'N/A',
                        'population' => $country->population ?? 'N/A',
                        'area_km2' => $country->area_km2 ?? 'N/A',
                    ];

                    // ChatGPT Service verwenden
                    $chatGptService = app(ChatGptService::class);
                    $result = $chatGptService->processPrompt($prompt, $countryData);

                    // Ergebnis in Notification anzeigen
                    Notification::make()
                        ->title($prompt->name)
                        ->body($result)
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
