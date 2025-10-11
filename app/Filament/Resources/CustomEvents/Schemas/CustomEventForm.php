<?php

namespace App\Filament\Resources\CustomEvents\Schemas;

use App\Models\AiPrompt;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\EventCategory;
use App\Models\EventDisplaySetting;
use App\Models\EventType;
use App\Services\ChatGptService;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CustomEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Hauptinformationen
                TextInput::make('title')
                    ->label('Titel')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('z.B. Brandschutzübung Frankfurt')
                    ->hint('KI-Verbesserung verfügbar')
                    ->hintIcon('heroicon-o-sparkles')
                    ->hintColor('primary')
                    ->hintAction(
                        \Filament\Forms\Components\Actions\Action::make('improve_title')
                            ->label('Mit KI verbessern')
                            ->icon('heroicon-o-sparkles')
                            ->requiresConfirmation()
                            ->modalHeading('Text mit KI verbessern')
                            ->modalDescription('Der aktuelle Titel wird an die KI gesendet und verbessert.')
                            ->modalSubmitActionLabel('Verbessern')
                            ->action(function (Set $set, Get $get, $state) {
                                if (empty($state)) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Kein Text vorhanden')
                                        ->body('Bitte geben Sie zuerst einen Text ein.')
                                        ->send();
                                    return;
                                }

                                // Prompt aus Datenbank laden
                                $prompt = AiPrompt::active()
                                    ->forModel('TextImprovement_Title')
                                    ->ordered()
                                    ->first();

                                if (!$prompt) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Kein KI-Prompt konfiguriert')
                                        ->body('Bitte konfigurieren Sie einen KI-Prompt für "Textverbesserung: Titel" in der Verwaltung.')
                                        ->send();
                                    return;
                                }

                                // Kontext-Daten sammeln
                                $description = strip_tags($get('popup_content') ?? '');
                                $selectedEventTypeIds = $get('eventTypes') ?? [];

                                // Ausgewählte Event-Typen Namen holen
                                $selectedEventTypes = 'Keine ausgewählt';
                                if (!empty($selectedEventTypeIds)) {
                                    $selectedTypes = EventType::whereIn('id', $selectedEventTypeIds)
                                        ->pluck('name')
                                        ->toArray();
                                    $selectedEventTypes = implode(', ', $selectedTypes);
                                }

                                // Alle verfügbaren Event-Typen
                                $availableEventTypes = EventType::active()
                                    ->ordered()
                                    ->pluck('name')
                                    ->implode(', ');

                                // ChatGPT Service verwenden
                                try {
                                    $chatGptService = app(ChatGptService::class);
                                    $result = $chatGptService->processPrompt($prompt, [
                                        'text' => $state,
                                        'description' => $description ?: 'Keine Beschreibung vorhanden',
                                        'selected_event_types' => $selectedEventTypes,
                                        'available_event_types' => $availableEventTypes,
                                    ]);

                                    // Ergebnis in Confirmation anzeigen
                                    Notification::make()
                                        ->success()
                                        ->title('Text wurde verbessert')
                                        ->body(new HtmlString('<div class="font-mono text-sm">' . nl2br(e($result)) . '</div>'))
                                        ->actions([
                                            \Filament\Notifications\Actions\Action::make('apply')
                                                ->label('Übernehmen')
                                                ->button()
                                                ->close()
                                                ->action(function () use ($set, $result) {
                                                    $set('title', trim($result));
                                                }),
                                            \Filament\Notifications\Actions\Action::make('cancel')
                                                ->label('Abbrechen')
                                                ->close(),
                                        ])
                                        ->persistent()
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Fehler bei der KI-Verarbeitung')
                                        ->body($e->getMessage())
                                        ->send();
                                }
                            })
                    )
                    ->columnSpanFull(),

                // Beschreibung-Feld ausgeblendet
                Textarea::make('description')
                    ->label('Beschreibung')
                    ->rows(3)
                    ->placeholder('Detaillierte Beschreibung des Events...')
                    ->hidden(),

                // Popup-Inhalt als Beschreibung
                RichEditor::make('popup_content')
                    ->label('Beschreibung')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'bulletList',
                        'orderedList',
                        'h2',
                        'h3',
                        'blockquote',
                        'codeBlock',
                    ])
                    ->helperText('HTML-Inhalt für die Popup-Anzeige. Unterstützt Formatierung und Links.')
                    ->hint('KI-Verbesserung verfügbar')
                    ->hintIcon('heroicon-o-sparkles')
                    ->hintColor('primary')
                    ->hintAction(
                        \Filament\Forms\Components\Actions\Action::make('improve_description')
                            ->label('Mit KI verbessern')
                            ->icon('heroicon-o-sparkles')
                            ->requiresConfirmation()
                            ->modalHeading('Text mit KI verbessern')
                            ->modalDescription('Die aktuelle Beschreibung wird an die KI gesendet und verbessert.')
                            ->modalSubmitActionLabel('Verbessern')
                            ->action(function (Set $set, Get $get, $state) {
                                // Strip HTML tags for the prompt
                                $plainText = strip_tags($state);

                                if (empty($plainText)) {
                                    Notification::make()
                                        ->warning()
                                        ->title('Kein Text vorhanden')
                                        ->body('Bitte geben Sie zuerst einen Text ein.')
                                        ->send();
                                    return;
                                }

                                // Prompt aus Datenbank laden
                                $prompt = AiPrompt::active()
                                    ->forModel('TextImprovement_Description')
                                    ->ordered()
                                    ->first();

                                if (!$prompt) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Kein KI-Prompt konfiguriert')
                                        ->body('Bitte konfigurieren Sie einen KI-Prompt für "Textverbesserung: Beschreibung" in der Verwaltung.')
                                        ->send();
                                    return;
                                }

                                // ChatGPT Service verwenden
                                try {
                                    $chatGptService = app(ChatGptService::class);
                                    $result = $chatGptService->processPrompt($prompt, ['text' => $plainText]);

                                    // Ergebnis in Confirmation anzeigen
                                    Notification::make()
                                        ->success()
                                        ->title('Text wurde verbessert')
                                        ->body(new HtmlString('<div class="prose prose-sm dark:prose-invert max-w-none">' . $result . '</div>'))
                                        ->actions([
                                            \Filament\Notifications\Actions\Action::make('apply')
                                                ->label('Übernehmen')
                                                ->button()
                                                ->close()
                                                ->action(function () use ($set, $result) {
                                                    $set('popup_content', $result);
                                                }),
                                            \Filament\Notifications\Actions\Action::make('cancel')
                                                ->label('Abbrechen')
                                                ->close(),
                                        ])
                                        ->persistent()
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Fehler bei der KI-Verarbeitung')
                                        ->body($e->getMessage())
                                        ->send();
                                }
                            })
                    )
                    ->columnSpanFull(),

                // Keep single event_type_id for backward compatibility but hide it
                Select::make('event_type_id')
                    ->label('Event-Typ (Alt)')
                    ->options(CustomEvent::getEventTypeOptions())
                    ->searchable()
                    ->preload()
                    ->hidden(),

                // New many-to-many event types with checkboxes
                CheckboxList::make('eventTypes')
                    ->label('Event-Typen')
                    ->relationship('eventTypes', 'name')
                    ->options(CustomEvent::getEventTypeOptions())
                    ->columns(2)
                    ->gridDirection('row')
                    ->required()
                    ->live()
                    ->helperText('Wählen Sie einen oder mehrere Event-Typen aus')
                    ->columnSpanFull(),

                // Manual icon selection (nur wenn Settings es erlauben und mehrere Event-Typen gewählt)
                Select::make('selected_display_event_type_id')
                    ->label('Anzuzeigendes Icon')
                    ->options(function (Get $get) {
                        $selectedEventTypeIds = $get('eventTypes') ?? [];
                        if (empty($selectedEventTypeIds)) {
                            return [];
                        }
                        return EventType::whereIn('id', $selectedEventTypeIds)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->visible(function (Get $get): bool {
                        $settings = EventDisplaySetting::current();
                        $selectedEventTypeIds = $get('eventTypes') ?? [];
                        return $settings->shouldShowManualSelection() &&
                               count($selectedEventTypeIds) > 1;
                    })
                    ->helperText('Wählen Sie, welches Icon auf der Karte angezeigt werden soll')
                    ->columnSpanFull(),

                // Icon-Vorschau (nur wenn Settings es erlauben)
                Placeholder::make('event_types_preview')
                    ->label('Gewählte Event-Typen & Icons')
                    ->content(function (Get $get) {
                        $selectedEventTypeIds = $get('eventTypes') ?? [];
                        if (empty($selectedEventTypeIds)) {
                            return new HtmlString('<span class="text-gray-500 text-sm">Keine Event-Typen ausgewählt</span>');
                        }

                        $eventTypes = EventType::whereIn('id', $selectedEventTypeIds)->get();
                        $html = '<div class="flex flex-wrap gap-3">';

                        foreach ($eventTypes as $eventType) {
                            $icon = $eventType->icon ?? 'fa-map-marker';
                            $color = $eventType->color ?? '#FF0000';

                            $html .= '<div class="flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg">';
                            $html .= '<i class="fas ' . htmlspecialchars($icon) . '" style="color: ' . htmlspecialchars($color) . '; font-size: 18px;"></i>';
                            $html .= '<span class="text-sm font-medium">' . htmlspecialchars($eventType->name) . '</span>';
                            $html .= '</div>';
                        }

                        $html .= '</div>';
                        return new HtmlString($html);
                    })
                    ->visible(function (Get $get): bool {
                        $settings = EventDisplaySetting::current();
                        $selectedEventTypeIds = $get('eventTypes') ?? [];
                        return $settings->shouldShowIconPreview() && !empty($selectedEventTypeIds);
                    })
                    ->columnSpanFull(),

                Select::make('event_category_id')
                    ->label('Kategorie')
                    ->options(function (Get $get) {
                        $eventTypeId = $get('event_type_id');
                        if (!$eventTypeId) {
                            return [];
                        }

                        return EventCategory::byEventType($eventTypeId)
                            ->active()
                            ->ordered()
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Wählen Sie zuerst einen Event-Typ aus')
                    ->hidden(),

                // Keep single country for backward compatibility but hide it
                Select::make('country_id')
                    ->label('Land (Alt)')
                    ->options(fn () => Country::query()
                        ->select('id', 'name_translations')
                        ->get()
                        ->mapWithKeys(fn (Country $c) => [$c->id => $c->getName('de')])
                        ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->hidden(),

                Select::make('priority')
                    ->label('Priorität')
                    ->options(CustomEvent::getPriorityOptions())
                    ->default('medium')
                    ->required(),


                TextInput::make('tags')
                    ->label('Tags')
                    ->placeholder('tag1, tag2, tag3')
                    ->helperText('Tags durch Kommas getrennt eingeben')
                    ->hidden(),

                // Status - nebeneinander in 2 Spalten
                \Filament\Schemas\Components\Grid::make(2)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Event auf der Karte anzeigen'),

                        Toggle::make('archived')
                            ->label('Archiviert')
                            ->default(false)
                            ->helperText('Archivierte Events werden noch 1 Jahr nach dem Enddatum auf der Karte angezeigt')
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $set('archived_at', now());
                                } else {
                                    $set('archived_at', null);
                                }
                            }),
                    ]),

                DateTimePicker::make('archived_at')
                    ->label('Archiviert am')
                    ->displayFormat('d.m.Y H:i')
                    ->disabled()
                    ->visible(fn (Get $get): bool => (bool) $get('archived')),

                DateTimePicker::make('start_date')
                    ->label('Startdatum')
                    ->required()
                    ->default(now())
                    ->displayFormat('d.m.Y H:i'),

                DateTimePicker::make('end_date')
                    ->label('Enddatum')
                    ->displayFormat('d.m.Y H:i')
                    ->helperText('Optional - für zeitlich begrenzte Events'),

                // Koordinaten - ausgeblendet, da jetzt über Länder-Zuordnung verwaltet
                TextInput::make('coordinates_paste')
                    ->label('Google Maps Koordinaten einfügen')
                    ->placeholder('z.B. 50.1109, 8.6821 oder 50°06\'39.2"N 8°40\'55.6"E')
                    ->helperText('Koordinaten aus Google Maps kopieren und hier einfügen')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                        if ($state) {
                            $coordinates = self::parseCoordinates($state);
                            if ($coordinates) {
                                $set('latitude', $coordinates['lat']);
                                $set('longitude', $coordinates['lng']);
                            }
                        }
                    })
                    ->hidden(),

                TextInput::make('latitude')
                    ->label('Breitengrad')
                    ->numeric()
                    ->minValue(-90)
                    ->maxValue(90)
                    ->step('any')
                    ->placeholder('50.1109')
                    ->helperText('Optional - Wert zwischen -90 und 90. Wenn leer, werden Länder-Koordinaten verwendet.')
                    ->live(onBlur: true)
                    ->hidden(),

                TextInput::make('longitude')
                    ->label('Längengrad')
                    ->numeric()
                    ->minValue(-180)
                    ->maxValue(180)
                    ->step('any')
                    ->placeholder('8.6821')
                    ->helperText('Optional - Wert zwischen -180 und 180. Wenn leer, werden Länder-Koordinaten verwendet.')
                    ->live(onBlur: true)
                    ->hidden(),

                Placeholder::make('osm_link')
                    ->label('')
                    ->content(function (Get $get) {
                        $lat = $get('latitude');
                        $lng = $get('longitude');

                        if ($lat && $lng) {
                            $zoom = 15;
                            $url = "https://www.openstreetmap.org/?mlat={$lat}&mlon={$lng}#map={$zoom}/{$lat}/{$lng}";

                            return new HtmlString(
                                '<a href="' . $url . '" target="_blank" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 rounded-lg transition-colors">
                                    Auf OpenStreetMap anzeigen
                                </a>'
                            );
                        }

                        return new HtmlString(
                            '<span class="text-gray-500 text-sm">Geben Sie Koordinaten ein, um die Position auf OpenStreetMap anzuzeigen.</span>'
                        );
                    })
                    ->hidden(),

                // Marker-Konfiguration - ausgeblendet für normale Nutzung
                ColorPicker::make('marker_color')
                    ->label('Marker-Farbe')
                    ->default('#FF0000')
                    ->helperText('Hauptfarbe des Markers auf der Karte')
                    ->hidden(),

                Select::make('marker_icon')
                    ->label('Marker Symbol')
                    ->options([
                        'fa-map-marker' => '📍 Standard Marker',
                        'fa-exclamation-triangle' => '⚠️ Warnung',
                        'fa-fire' => '🔥 Feuer',
                        'fa-tint' => '💧 Wasser',
                        'fa-cloud' => '☁️ Wolke',
                        'fa-bolt' => '⚡ Blitz',
                        'fa-building' => '🏢 Gebäude',
                        'fa-car' => '🚗 Fahrzeug',
                        'fa-plane' => '✈️ Flugzeug',
                        'fa-ship' => '🚢 Schiff',
                        'fa-train' => '🚂 Zug',
                        'fa-bus' => '🚌 Bus',
                        'fa-ambulance' => '🚑 Krankenwagen',
                        'fa-fire-extinguisher' => '🧯 Feuerlöscher',
                        'fa-shield-alt' => '🛡️ Schutz',
                        'fa-user-shield' => '👤 Benutzer-Schutz',
                        'fa-exclamation-circle' => '❌ Ausrufezeichen',
                        'fa-info-circle' => 'ℹ️ Information',
                        'fa-check-circle' => '✅ Bestätigung',
                        'fa-clock' => '🕐 Uhr',
                        'fa-calendar' => '📅 Kalender',
                        'fa-flag' => '🚩 Flagge',
                        'fa-star' => '⭐ Stern',
                        'fa-heart' => '❤️ Herz',
                        'fa-home' => '🏠 Haus',
                        'fa-hospital' => '🏥 Krankenhaus',
                        'fa-school' => '🏫 Schule',
                        'fa-university' => '🎓 Universität',
                        'fa-industry' => '🏭 Industrie',
                        'fa-shopping-cart' => '🛒 Einkaufswagen',
                        'fa-utensils' => '🍴 Restaurant',
                        'fa-coffee' => '☕ Café',
                        'fa-beer' => '🍺 Bar',
                        'fa-hotel' => '🏨 Hotel',
                        'fa-campground' => '🏕️ Camping',
                        'fa-mountain' => '⛰️ Berg',
                        'fa-tree' => '🌳 Baum',
                        'fa-leaf' => '🍃 Blatt',
                        'fa-sun' => '☀️ Sonne',
                        'fa-moon' => '🌙 Mond',
                        'fa-cloud-rain' => '🌧️ Regen',
                        'fa-snowflake' => '❄️ Schnee',
                        'fa-wind' => '💨 Wind',
                        'fa-thermometer-half' => '🌡️ Temperatur',
                        'fa-tachometer-alt' => '📊 Geschwindigkeit',
                        'fa-weight-hanging' => '⚖️ Gewicht',
                        'fa-ruler' => '📏 Lineal',
                        'fa-compass' => '🧭 Kompass',
                        'fa-map' => '🗺️ Karte',
                        'fa-globe' => '🌍 Globus',
                        'fa-location-arrow' => '📍 Pfeil',
                        'fa-crosshairs' => '🎯 Ziel',
                        'fa-bullseye' => '🎯 Zielscheibe',
                        'fa-dot-circle' => '🔘 Punkt',
                        'fa-circle' => '⭕ Kreis',
                        'fa-square' => '⬜ Quadrat',
                        'fa-diamond' => '💎 Diamant',
                        'fa-hexagon' => '⬡ Sechseck',
                        'fa-octagon' => '⬢ Achteck',
                    ])
                    ->default('fa-map-marker')
                    ->searchable()
                    ->helperText('Symbol für den Marker auf der Karte')
                    ->hidden(),

                ColorPicker::make('icon_color')
                    ->label('Symbol-Farbe')
                    ->default('#FFFFFF')
                    ->helperText('Farbe des Symbols im Marker')
                    ->hidden(),

                Select::make('marker_size')
                    ->label('Marker-Größe')
                    ->options(CustomEvent::getMarkerSizeOptions())
                    ->default('medium')
                    ->helperText('Größe des Markers auf der Karte')
                    ->hidden(),

                // Datenquelle am Ende
                Select::make('data_source')
                    ->label('Datenquelle')
                    ->options([
                        'manual' => 'Manuell erfasst',
                        'passolution_infosystem' => 'Passolution Infosystem',
                        'api_import' => 'API Import',
                        'other' => 'Andere',
                    ])
                    ->default('manual')
                    ->disabled()
                    ->dehydrated()
                    ->hidden(),

                TextInput::make('data_source_id')
                    ->label('Datenquellen-ID')
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Referenz-ID aus der Ursprungsdatenquelle')
                    ->hidden(),

            ]);
    }

    /**
     * Parse coordinates from various formats
     */
    private static function parseCoordinates(string $input): ?array
    {
        $input = trim($input);

        // Format: 50.1109, 8.6821
        if (preg_match('/^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/', $input, $matches)) {
            return [
                'lat' => (float) $matches[1],
                'lng' => (float) $matches[2],
            ];
        }

        // Format: 50°06'39.2"N 8°40'55.6"E
        if (preg_match('/(\d+)°(\d+)\'([\d.]+)"([NS])\s+(\d+)°(\d+)\'([\d.]+)"([EW])/', $input, $matches)) {
            $lat = (float) $matches[1] + (float) $matches[2] / 60 + (float) $matches[3] / 3600;
            $lng = (float) $matches[5] + (float) $matches[6] / 60 + (float) $matches[7] / 3600;

            if ($matches[4] === 'S') {
                $lat = -$lat;
            }
            if ($matches[8] === 'W') {
                $lng = -$lng;
            }

            return ['lat' => $lat, 'lng' => $lng];
        }

        // Format: 50.1109°N, 8.6821°E
        if (preg_match('/(\d+\.?\d*)°([NS])\s*,\s*(\d+\.?\d*)°([EW])/', $input, $matches)) {
            $lat = (float) $matches[1];
            $lng = (float) $matches[3];

            if ($matches[2] === 'S') {
                $lat = -$lat;
            }
            if ($matches[4] === 'W') {
                $lng = -$lng;
            }

            return ['lat' => $lat, 'lng' => $lng];
        }

        return null;
    }
}
