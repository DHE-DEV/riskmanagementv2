<?php

namespace App\Filament\Resources\CustomEvents\Schemas;

use App\Models\Country;
use App\Models\CustomEvent;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

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
                    ->placeholder('Beschreiben Sie hier den Inhalt, der im Popup angezeigt werden soll...')
                    ->columnSpanFull(),

                Select::make('event_type')
                    ->label('Event-Typ')
                    ->options(CustomEvent::getEventTypeOptions())
                    ->required()
                    ->searchable(),

                Select::make('country_id')
                    ->label('Land')
                    ->options(fn () => Country::query()
                        ->select('id', 'name_translations')
                        ->get()
                        ->mapWithKeys(fn (Country $c) => [$c->id => $c->getName('de')])
                        ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                Select::make('priority')
                    ->label('Priorität')
                    ->options(CustomEvent::getPriorityOptions())
                    ->default('medium')
                    ->required(),

                TextInput::make('category')
                    ->label('Kategorie')
                    ->placeholder('z.B. Übung, Notfall, Wartung')
                    ->maxLength(100),

                TextInput::make('tags')
                    ->label('Tags')
                    ->placeholder('tag1, tag2, tag3')
                    ->helperText('Tags durch Kommas getrennt eingeben'),

                // Status & Zeit
                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true)
                    ->helperText('Event auf der Karte anzeigen'),

                DateTimePicker::make('start_date')
                    ->label('Startdatum')
                    ->required()
                    ->default(now())
                    ->displayFormat('d.m.Y H:i'),

                DateTimePicker::make('end_date')
                    ->label('Enddatum')
                    ->displayFormat('d.m.Y H:i')
                    ->helperText('Optional - für zeitlich begrenzte Events'),

                // Koordinaten
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
                    }),

                TextInput::make('latitude')
                    ->label('Breitengrad')
                    ->required()
                    ->numeric()
                    ->step(0.000000000000001)
                    ->placeholder('50.1109'),

                TextInput::make('longitude')
                    ->label('Längengrad')
                    ->required()
                    ->numeric()
                    ->step(0.000000000000001)
                    ->placeholder('8.6821'),

                // Marker-Konfiguration
                ColorPicker::make('marker_color')
                    ->label('Marker-Farbe')
                    ->default('#FF0000')
                    ->helperText('Hauptfarbe des Markers'),

                Select::make('marker_icon')
                    ->label('FontAwesome Symbol')
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
                    ->helperText('Symbol für den Marker'),

                ColorPicker::make('icon_color')
                    ->label('Symbol-Farbe')
                    ->default('#FFFFFF')
                    ->helperText('Farbe des Symbols'),

                Select::make('marker_size')
                    ->label('Marker-Größe')
                    ->options(CustomEvent::getMarkerSizeOptions())
                    ->default('medium')
                    ->required()
                    ->helperText('Größe des Markers auf der Karte'),

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
