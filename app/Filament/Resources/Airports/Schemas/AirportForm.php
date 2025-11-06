<?php

namespace App\Filament\Resources\Airports\Schemas;

use App\Models\City;
use App\Models\Country;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AirportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        // Linke Spalte
                        \Filament\Schemas\Components\Grid::make(1)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255),

                                Select::make('country_id')
                                    ->label('Land')
                                    ->options(function () {
                                        return Country::all()->mapWithKeys(function ($country) {
                                            return [$country->id => $country->getName('de')];
                                        })->toArray();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn ($set) => $set('city_id', null)),

                                Select::make('city_id')
                                    ->label('Stadt')
                                    ->options(function (Get $get, $record) {
                                        $countryId = $get('country_id');

                                        // Beim Bearbeiten: Wenn kein Land ausgewählt, nutze das Land des aktuellen Flughafens
                                        if (!$countryId && $record) {
                                            $countryId = $record->country_id;
                                        }

                                        if (!$countryId) {
                                            return [];
                                        }

                                        return City::where('country_id', $countryId)->get()->mapWithKeys(function ($city) {
                                            return [$city->id => $city->getName('de')];
                                        })->toArray();
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('website')
                                    ->label('Website')
                                    ->url()
                                    ->placeholder('https://example.com')
                                    ->helperText('Offizielle Website des Flughafens')
                                    ->maxLength(2048),

                                TextInput::make('security_timeslot_url')
                                    ->label('Zeitfenster-Reservierung für Sicherheitskontrolle')
                                    ->url()
                                    ->placeholder('https://example.com/timeslot-booking')
                                    ->helperText('URL zum Buchungssystem für Sicherheitskontroll-Zeitfenster')
                                    ->maxLength(2048),

                                Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true),
                            ]),

                        // Rechte Spalte
                        \Filament\Schemas\Components\Grid::make(1)
                            ->schema([
                                \Filament\Schemas\Components\Grid::make(2)
                                    ->schema([
                                        TextInput::make('iata_code')
                                            ->label('IATA Code')
                                            ->required()
                                            ->maxLength(3)
                                            ->unique(ignoreRecord: true),

                                        TextInput::make('icao_code')
                                            ->label('ICAO Code')
                                            ->required()
                                            ->maxLength(4)
                                            ->unique(ignoreRecord: true),
                                    ]),

                                Select::make('type')
                                    ->label('Typ')
                                    ->options([
                                        'international' => 'Internationaler Flughafen',
                                        'large_airport' => 'Großer Flughafen',
                                        'medium_airport' => 'Mittlerer Flughafen',
                                        'small_airport' => 'Kleiner Flughafen',
                                        'heliport' => 'Hubschrauberlandeplatz',
                                        'seaplane_base' => 'Wasserflugzeugbasis',
                                    ])
                                    ->default('medium_airport')
                                    ->native(false)
                                    ->required(),

                                TextInput::make('google_maps_coordinates')
                                    ->label('Google Maps Koordinaten einfügen')
                                    ->placeholder('z.B. 50.1109, 8.6821 oder 52.0097, -76.5467')
                                    ->helperText('Koordinaten aus Google Maps hier einfügen - automatische Übernahme in Breiten- und Längengrad. Unterstützt positive und negative Werte.')
                                    ->live(onBlur: true)
                                    ->dehydrated(false)
                                    ->afterStateUpdated(function ($set, ?string $state) {
                                        if (!$state) {
                                            return;
                                        }

                                        // Parse Google Maps coordinate formats
                                        // Entferne alle Zeichen außer Zahlen, Punkt, Komma und Minus
                                        $cleaned = preg_replace('/[^\d.,\-]/', ' ', $state);
                                        $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));

                                        // Trenne nach Komma oder Leerzeichen
                                        if (strpos($cleaned, ',') !== false) {
                                            $parts = explode(',', $cleaned);
                                        } else {
                                            $parts = explode(' ', $cleaned);
                                        }

                                        if (count($parts) >= 2) {
                                            $lat = trim($parts[0]);
                                            $lng = trim($parts[1]);

                                            if (is_numeric($lat) && is_numeric($lng)) {
                                                $set('lat', $lat);
                                                $set('lng', $lng);
                                            }
                                        }
                                    }),

                                \Filament\Schemas\Components\Grid::make(2)
                                    ->schema([
                                        TextInput::make('lat')
                                            ->label('Breitengrad')
                                            ->rule('numeric')
                                            ->rule('min:-90')
                                            ->rule('max:90')
                                            ->placeholder('z.B. 50.1109 oder -76.5467')
                                            ->helperText('Werte zwischen -90 und +90')
                                            ->inputMode('decimal')
                                            ->extraInputAttributes(['step' => 'any']),

                                        TextInput::make('lng')
                                            ->label('Längengrad')
                                            ->rule('numeric')
                                            ->rule('min:-180')
                                            ->rule('max:180')
                                            ->placeholder('z.B. 8.6821 oder -76.5467')
                                            ->helperText('Werte zwischen -180 und +180')
                                            ->inputMode('decimal')
                                            ->extraInputAttributes(['step' => 'any']),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
