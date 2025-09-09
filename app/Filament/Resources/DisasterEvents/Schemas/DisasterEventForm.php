<?php

namespace App\Filament\Resources\DisasterEvents\Schemas;

use App\Models\DisasterEvent;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DisasterEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('severity')
                    ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'])
                    ->default('low')
                    ->required(),
                Select::make('event_type_id')
                    ->label('Event-Typ')
                    ->options(DisasterEvent::getEventTypeOptions())
                    ->required()
                    ->searchable()
                    ->preload(),
                // Koordinaten-Gruppe
                Section::make([
                    TextInput::make('lat')
                        ->label('Breitengrad')
                        ->numeric()
                        ->placeholder('z.B. 52.5200')
                        ->helperText('Breitengrad zwischen -90 und 90')
                        ->suffixAction(
                            Action::make('paste_coordinates')
                                ->label('Koordinaten einfügen')
                                ->icon('heroicon-o-clipboard')
                                ->color('gray')
                                ->size('sm')
                                ->tooltip('Fügt Koordinaten aus der Zwischenablage ein (z.B. von Google Maps)')
                                ->action(function ($record, $livewire) {
                                    return self::pasteCoordinatesFromClipboard($record, $livewire);
                                })
                                ->extraAttributes([
                                    'x-data' => '{}',
                                    'x-init' => '
                                        $el.addEventListener("click", function() {
                                            // Event-Listener für das Breitengrad-Feld hinzufügen
                                            const latField = document.querySelector("input[name=\"lat\"]");
                                            if (latField) {
                                                const pasteHandler = function(e) {
                                                    e.preventDefault();
                                                    
                                                    // Text aus der Zwischenablage holen
                                                    const pastedText = (e.clipboardData || window.clipboardData).getData("text");
                                                    
                                                    // Koordinaten parsen
                                                    const coordinates = parseCoordinatesFromText(pastedText);
                                                    if (coordinates) {
                                                        // Koordinaten in die Felder eintragen
                                                        const lngField = document.querySelector("input[name=\"lng\"]");
                                                        if (lngField) {
                                                            latField.value = coordinates.lat;
                                                            lngField.value = coordinates.lng;
                                                            
                                                            // Livewire-Events auslösen
                                                            latField.dispatchEvent(new Event("input", { bubbles: true }));
                                                            lngField.dispatchEvent(new Event("input", { bubbles: true }));
                                                            
                                                            // Erfolgsbenachrichtigung
                                                            window.Livewire.find(document.querySelector("[wire\\:id]").getAttribute("wire:id")).call("showCoordinatePasteSuccess", coordinates.lat, coordinates.lng);
                                                        }
                                                    } else {
                                                        // Fehlerbenachrichtigung
                                                        window.Livewire.find(document.querySelector("[wire\\:id]").getAttribute("wire:id")).call("showCoordinatePasteError", pastedText);
                                                    }
                                                    
                                                    // Event-Listener entfernen
                                                    latField.removeEventListener("paste", pasteHandler);
                                                };
                                                
                                                latField.addEventListener("paste", pasteHandler);
                                                
                                                // Benutzer auffordern, Koordinaten einzufügen
                                                latField.focus();
                                                latField.select();
                                                
                                                // Info-Benachrichtigung
                                                window.Livewire.find(document.querySelector("[wire\\:id]").getAttribute("wire:id")).call("showCoordinatePasteInfo");
                                            }
                                        });
                                        
                                        function parseCoordinatesFromText(text) {
                                            // Entferne Leerzeichen und Zeilenumbrüche
                                            text = text.trim().replace(/\\s+/g, " ");
                                            
                                            // Verschiedene Koordinaten-Formate erkennen
                                            
                                            // Format 1: 52.5200, 13.4050 (Komma-getrennt)
                                            let match1 = text.match(/^(-?\\d+\\.\\d+)\\s*,\\s*(-?\\d+\\.\\d+)$/);
                                            if (match1) {
                                                return { lat: parseFloat(match1[1]), lng: parseFloat(match1[2]) };
                                            }
                                            
                                            // Format 2: 52.5200 13.4050 (Leerzeichen-getrennt)
                                            let match2 = text.match(/^(-?\\d+\\.\\d+)\\s+(-?\\d+\\.\\d+)$/);
                                            if (match2) {
                                                return { lat: parseFloat(match2[1]), lng: parseFloat(match2[2]) };
                                            }
                                            
                                            // Format 3: Google Maps URL mit Koordinaten
                                            let match3 = text.match(/@(-?\\d+\\.\\d+),(-?\\d+\\.\\d+)/);
                                            if (match3) {
                                                return { lat: parseFloat(match3[1]), lng: parseFloat(match3[2]) };
                                            }
                                            
                                            // Format 4: Google Maps URL mit Koordinaten (andere Variante)
                                            let match4 = text.match(/maps\\/place\\/[^@]+@(-?\\d+\\.\\d+),(-?\\d+\\.\\d+)/);
                                            if (match4) {
                                                return { lat: parseFloat(match4[1]), lng: parseFloat(match4[2]) };
                                            }
                                            
                                            // Format 5: Koordinaten mit Grad/Minuten/Sekunden (vereinfacht)
                                            let match5 = text.match(/^(-?\\d+)°\\s*(\\d+)\'\\s*(\\d+\\.?\\d*)"\\s*[NS]\\s*,?\\s*(-?\\d+)°\\s*(\\d+)\'\\s*(\\d+\\.?\\d*)"\\s*[EW]$/i);
                                            if (match5) {
                                                let lat = parseInt(match5[1]) + parseInt(match5[2])/60 + parseFloat(match5[3])/3600;
                                                let lng = parseInt(match5[4]) + parseInt(match5[5])/60 + parseFloat(match5[6])/3600;
                                                if (match5[0].toUpperCase().includes("S")) lat = -lat;
                                                if (match5[0].toUpperCase().includes("W")) lng = -lng;
                                                return { lat: lat, lng: lng };
                                            }
                                            
                                            return null;
                                        }
                                    ',
                                ])
                        ),
                    TextInput::make('lng')
                        ->label('Längengrad')
                        ->numeric()
                        ->placeholder('z.B. 13.4050')
                        ->helperText('Längengrad zwischen -180 und 180'),
                ])->columns(2),

                TextInput::make('radius_km')
                    ->numeric(),

                // Land-Auswahl mit Länderermittlung-Schaltfläche
                Select::make('country_id')
                    ->label('Land')
                    ->relationship(
                        'country',
                        'iso_code',
                        fn ($query) => $query
                            ->withoutGlobalScopes([SoftDeletingScope::class]) // Entferne SoftDelete Scope
                            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))")
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getName('de'))
                    ->searchable(['iso_code', 'iso3_code'])
                    ->getSearchResultsUsing(fn (string $search) => \App\Models\Country::query()
                        ->withoutGlobalScopes([SoftDeletingScope::class])
                        ->where(function ($query) use ($search) {
                            $query->where('iso_code', 'like', "%{$search}%")
                                ->orWhere('iso3_code', 'like', "%{$search}%")
                                ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))) LIKE LOWER(?)", ["%{$search}%"])
                                ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en'))) LIKE LOWER(?)", ["%{$search}%"]);
                        })
                        ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))")
                        ->limit(50)
                        ->get()
                        ->pluck('name_translations', 'id')
                        ->map(fn ($translations, $id) => $translations['de'] ?? $translations['en'] ?? 'Unbekannt')
                    )
                    ->preload()
                    ->suffixAction(
                        Action::make('detect_country')
                            ->label('Land aus Koordinaten ermitteln')
                            ->icon('heroicon-o-map-pin')
                            ->color('info')
                            ->size('sm')
                            ->tooltip('Ermittelt automatisch das Land basierend auf den Geokoordinaten')
                            ->requiresConfirmation()
                            ->modalHeading('Land aus Koordinaten ermitteln')
                            ->modalDescription('Diese Aktion wird das Land automatisch basierend auf den Breiten- und Längengrad-Koordinaten ermitteln.')
                            ->modalSubmitActionLabel('Land ermitteln')
                            ->modalCancelActionLabel('Abbrechen')
                            ->action(function ($record, $livewire) {
                                return self::detectCountryFromCoordinates($record, $livewire);
                            })
                    ),

                Select::make('region_id')
                    ->relationship('region', 'code', fn ($query) => $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))"))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getName('de'))
                    ->searchable()
                    ->preload(),
                Select::make('city_id')
                    ->relationship('city', 'id', fn ($query) => $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))"))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getName('de'))
                    ->searchable()
                    ->preload(),
                TextInput::make('affected_areas'),
                DatePicker::make('event_date')
                    ->required(),
                DateTimePicker::make('start_time'),
                DateTimePicker::make('end_time'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('impact_assessment'),
                TextInput::make('travel_recommendations'),
                Textarea::make('official_sources')
                    ->columnSpanFull(),
                Textarea::make('media_coverage')
                    ->columnSpanFull(),
                TextInput::make('tourism_impact'),
                TextInput::make('external_sources')
                    ->required(),
                DateTimePicker::make('last_updated')
                    ->required(),
                TextInput::make('confidence_score')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('processing_status')
                    ->options(['pending' => 'Pending', 'processed' => 'Processed', 'failed' => 'Failed', 'none' => 'None'])
                    ->default('none')
                    ->required(),
                Textarea::make('ai_summary')
                    ->columnSpanFull(),
                Textarea::make('ai_recommendations')
                    ->columnSpanFull(),
                Textarea::make('crisis_communication')
                    ->columnSpanFull(),
                TextInput::make('keywords'),
                TextInput::make('magnitude')
                    ->numeric(),
                Textarea::make('casualties')
                    ->columnSpanFull(),
                Textarea::make('economic_impact')
                    ->columnSpanFull(),
                Textarea::make('infrastructure_damage')
                    ->columnSpanFull(),
                Textarea::make('emergency_response')
                    ->columnSpanFull(),
                Textarea::make('recovery_status')
                    ->columnSpanFull(),
                TextInput::make('external_id'),
                TextInput::make('gdacs_event_id'),
                TextInput::make('gdacs_episode_id'),
                Select::make('gdacs_alert_level')
                    ->options(['Green' => 'Green', 'Orange' => 'Orange', 'Red' => 'Red']),
                TextInput::make('gdacs_alert_score')
                    ->numeric(),
                TextInput::make('gdacs_episode_alert_level'),
                TextInput::make('gdacs_episode_alert_score')
                    ->numeric(),
                TextInput::make('gdacs_event_name'),
                TextInput::make('gdacs_calculation_type'),
                TextInput::make('gdacs_severity_value')
                    ->numeric(),
                TextInput::make('gdacs_severity_unit'),
                Textarea::make('gdacs_severity_text')
                    ->columnSpanFull(),
                TextInput::make('gdacs_population_value')
                    ->numeric(),
                TextInput::make('gdacs_population_unit'),
                Textarea::make('gdacs_population_text')
                    ->columnSpanFull(),
                TextInput::make('gdacs_vulnerability')
                    ->numeric(),
                TextInput::make('gdacs_iso3'),
                TextInput::make('gdacs_country'),
                TextInput::make('gdacs_glide'),
                TextInput::make('gdacs_bbox'),
                Textarea::make('gdacs_cap_url')
                    ->columnSpanFull(),
                Textarea::make('gdacs_icon_url')
                    ->columnSpanFull(),
                TextInput::make('gdacs_version')
                    ->numeric(),
                Toggle::make('gdacs_temporary')
                    ->required(),
                Toggle::make('gdacs_is_current')
                    ->required(),
                TextInput::make('gdacs_duration_weeks')
                    ->numeric(),
                TextInput::make('gdacs_resources'),
                Textarea::make('gdacs_map_image')
                    ->columnSpanFull(),
                Textarea::make('gdacs_map_link')
                    ->columnSpanFull(),
                DateTimePicker::make('gdacs_date_added'),
                DateTimePicker::make('gdacs_date_modified'),
                TextInput::make('weather_conditions'),
                TextInput::make('evacuation_info'),
                TextInput::make('transportation_impact'),
                TextInput::make('accommodation_impact'),
                TextInput::make('communication_status'),
                TextInput::make('health_services_status'),
                TextInput::make('utility_services_status'),
                TextInput::make('border_crossings_status'),
            ]);
    }

    /**
     * Länderermittlung basierend auf Koordinaten
     */
    protected static function detectCountryFromCoordinates($record, $livewire): void
    {
        try {
            // Hole die aktuellen Koordinaten aus dem Formular
            $formData = $livewire->form->getState();
            $lat = $formData['lat'] ?? null;
            $lng = $formData['lng'] ?? null;

            // Prüfe ob Koordinaten vorhanden sind
            if (empty($lat) || empty($lng)) {
                \Filament\Notifications\Notification::make()
                    ->title('Keine Koordinaten vorhanden')
                    ->body('Bitte geben Sie zuerst Breiten- und Längengrad ein.')
                    ->warning()
                    ->send();

                return;
            }

            // Validiere Koordinaten
            if (! is_numeric($lat) || ! is_numeric($lng) ||
                $lat < -90 || $lat > 90 ||
                $lng < -180 || $lng > 180) {
                \Filament\Notifications\Notification::make()
                    ->title('Ungültige Koordinaten')
                    ->body('Die eingegebenen Koordinaten sind ungültig. Breitengrad: -90 bis 90, Längengrad: -180 bis 180.')
                    ->warning()
                    ->send();

                return;
            }

            $lat = (float) $lat;
            $lng = (float) $lng;

            // Verwende den ReverseGeocodingService für die Länderermittlung
            $reverseGeocodingService = app(\App\Services\ReverseGeocodingService::class);
            $locationInfo = $reverseGeocodingService->getLocationFromCoordinates($lat, $lng);

            if ($locationInfo['country']) {
                // Setze das Land im Formular und behalte die Koordinaten bei
                $livewire->form->fill([
                    'lat' => $lat,
                    'lng' => $lng,
                    'country_id' => $locationInfo['country']['id'],
                ]);

                // Erfolgsbenachrichtigung
                \Filament\Notifications\Notification::make()
                    ->title('Land erfolgreich ermittelt')
                    ->body("Land: {$locationInfo['country']['name']} ({$locationInfo['country']['iso_code']})")
                    ->success()
                    ->send();

                // Log für Debugging
                \Illuminate\Support\Facades\Log::info('Land automatisch aus Koordinaten ermittelt', [
                    'coordinates' => ['lat' => $lat, 'lng' => $lng],
                    'country' => $locationInfo['country'],
                    'record_id' => $record->id ?? 'new',
                ]);

            } else {
                // Kein Land gefunden
                \Filament\Notifications\Notification::make()
                    ->title('Kein Land gefunden')
                    ->body('Für die angegebenen Koordinaten konnte kein Land ermittelt werden.')
                    ->warning()
                    ->send();

                \Illuminate\Support\Facades\Log::warning('Kein Land für Koordinaten gefunden', [
                    'coordinates' => ['lat' => $lat, 'lng' => $lng],
                    'record_id' => $record->id ?? 'new',
                ]);
            }

        } catch (\Exception $e) {
            // Fehlerbehandlung
            \Illuminate\Support\Facades\Log::error('Fehler bei der automatischen Länderermittlung', [
                'record_id' => $record->id ?? 'new',
                'error' => $e->getMessage(),
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Fehler bei der Länderermittlung')
                ->body('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut oder wählen Sie das Land manuell aus.')
                ->danger()
                ->send();
        }
    }

    /**
     * Koordinaten aus der Zwischenablage einfügen
     */
    protected static function pasteCoordinatesFromClipboard($record, $livewire): void
    {
        // Diese Methode wird nicht mehr benötigt, da das JavaScript direkt in der Action eingebettet ist
        // Die Funktionalität wird über Alpine.js und das x-init Attribut der Action bereitgestellt
    }

    /**
     * Erfolgsbenachrichtigung für eingefügte Koordinaten
     */
    public function showCoordinatePasteSuccess($lat, $lng): void
    {
        \Filament\Notifications\Notification::make()
            ->title('Koordinaten erfolgreich eingefügt')
            ->body("Breitengrad: {$lat}, Längengrad: {$lng}")
            ->success()
            ->send();
    }

    /**
     * Fehlerbenachrichtigung für ungültige Koordinaten
     */
    public function showCoordinatePasteError($text): void
    {
        \Filament\Notifications\Notification::make()
            ->title('Ungültige Koordinaten')
            ->body("Der Text '{$text}' konnte nicht als gültige Koordinaten erkannt werden. Unterstützte Formate: 52.5200, 13.4050 oder Google Maps URLs.")
            ->warning()
            ->send();
    }

    /**
     * Info-Benachrichtigung für Koordinaten-Einfüge-Modus
     */
    public function showCoordinatePasteInfo(): void
    {
        \Filament\Notifications\Notification::make()
            ->title('Koordinaten einfügen')
            ->body('Bitte fügen Sie die Koordinaten in das Breitengrad-Feld ein. Unterstützte Formate: 52.5200, 13.4050 oder Google Maps URLs.')
            ->info()
            ->send();
    }
}
