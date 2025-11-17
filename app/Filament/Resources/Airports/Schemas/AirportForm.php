<?php

namespace App\Filament\Resources\Airports\Schemas;

use App\Models\City;
use App\Models\Country;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AirportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Erstes Grid - Grundinformationen
                \Filament\Schemas\Components\Grid::make(2)
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 2,
                    ])
                    ->schema([
                        // Linke Spalte
                        \Filament\Schemas\Components\Grid::make(1)
                            ->columnSpan(1)
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
                                        $currentCityId = $get('city_id');

                                        if (!$countryId && $record) {
                                            $countryId = $record->country_id;
                                        }

                                        if (!$countryId) {
                                            return [];
                                        }

                                        $cities = City::where('country_id', $countryId)->get()->mapWithKeys(function ($city) {
                                            return [$city->id => $city->getName('de')];
                                        });

                                        if ($currentCityId && !$cities->has($currentCityId)) {
                                            $currentCity = City::find($currentCityId);
                                            if ($currentCity) {
                                                $cities->put($currentCityId, $currentCity->getName('de'));
                                            }
                                        }

                                        return $cities->toArray();
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

                                Toggle::make('operates_24h')
                                    ->label('24h Betrieb für Passagierflugzeuge')
                                    ->helperText('Ist der Flughafen 24 Stunden täglich für Passagierflugzeuge in Betrieb?')
                                    ->default(false),
                            ]),

                        // Rechte Spalte
                        \Filament\Schemas\Components\Grid::make(1)
                            ->columnSpan(1)
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
                                    ->placeholder('z.B. 50.1109, 8.6821')
                                    ->helperText('Koordinaten aus Google Maps hier einfügen')
                                    ->live(onBlur: true)
                                    ->dehydrated(false)
                                    ->afterStateUpdated(function ($set, ?string $state) {
                                        if (!$state) {
                                            return;
                                        }

                                        $cleaned = preg_replace('/[^\d.,\-]/', ' ', $state);
                                        $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));

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
                                            ->placeholder('z.B. 50.1109')
                                            ->helperText('Werte zwischen -90 und +90')
                                            ->inputMode('decimal')
                                            ->extraInputAttributes(['step' => 'any']),

                                        TextInput::make('lng')
                                            ->label('Längengrad')
                                            ->rule('numeric')
                                            ->rule('min:-180')
                                            ->rule('max:180')
                                            ->placeholder('z.B. 8.6821')
                                            ->helperText('Werte zwischen -180 und +180')
                                            ->inputMode('decimal')
                                            ->extraInputAttributes(['step' => 'any']),
                                    ]),
                            ]),
                    ]),

                // Zweites Grid - Zusätzliche Informationen
                \Filament\Schemas\Components\Grid::make(2)
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 2,
                    ])
                    ->schema([
                        // Linke Spalte - Grid 1 (Lounges + Mobilitätsangebote)
                        \Filament\Schemas\Components\Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                // Lounges Section
                                Section::make('Lounges')
                                    ->description('Informationen zu verfügbaren Lounges am Flughafen')
                                    ->schema([
                                        Repeater::make('lounges')
                                            ->label('')
                                            ->schema([
                                                \Filament\Schemas\Components\Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('Name der Lounge')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->placeholder('z.B. Lufthansa Business Lounge')
                                                            ->columnSpan(2),

                                                        TextInput::make('location')
                                                            ->label('Standort')
                                                            ->maxLength(255)
                                                            ->placeholder('z.B. Terminal 1, Ebene 3')
                                                            ->columnSpan(1),

                                                        TextInput::make('access')
                                                            ->label('Zugang')
                                                            ->maxLength(255)
                                                            ->placeholder('z.B. Business Class, Priority Pass')
                                                            ->columnSpan(1),

                                                        Toggle::make('children_welcome')
                                                            ->label('Kinder willkommen')
                                                            ->default(false)
                                                            ->columnSpan(1),

                                                        TextInput::make('price_per_person')
                                                            ->label('Preis pro Person ab')
                                                            ->numeric()
                                                            ->step(0.01)
                                                            ->suffix('EUR')
                                                            ->placeholder('z.B. 35.00')
                                                            ->helperText('Preis für den Lounge-Zugang pro Person')
                                                            ->columnSpan(1),

                                                        TextInput::make('url')
                                                            ->label('Website/Info-URL')
                                                            ->url()
                                                            ->maxLength(2048)
                                                            ->placeholder('https://...')
                                                            ->columnSpan(2),
                                                    ]),
                                            ])
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->itemLabel(function (array $state): ?string {
                                                if (!isset($state['name'])) {
                                                    return null;
                                                }

                                                $label = $state['name'];
                                                if (!empty($state['location'])) {
                                                    $label .= ' - ' . $state['location'];
                                                }

                                                return $label;
                                            })
                                            ->addActionLabel('Lounge hinzufügen')
                                            ->reorderable(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),

                                // Mobilitätsangebote Section
                                Section::make('Mobilitätsangebote')
                                    ->description('Verkehrs- und Mobilitätsoptionen am Flughafen')
                                    ->schema([
                                        // Mietwagen
                                        \Filament\Schemas\Components\Fieldset::make('Mietwagen')
                                            ->schema([
                                                Toggle::make('mobility_options.car_rental.available')
                                                    ->label('Verfügbar')
                                                    ->default(false)
                                                    ->reactive()
                                                    ->columnSpanFull(),

                                                Repeater::make('mobility_options.car_rental.providers')
                                                    ->label('Anbieter')
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('Anbieter')
                                                            ->required()
                                                            ->placeholder('z.B. Sixt, Hertz, Avis')
                                                            ->columnSpanFull(),

                                                        TextInput::make('url')
                                                            ->label('Website/Buchungs-URL')
                                                            ->url()
                                                            ->maxLength(2048)
                                                            ->placeholder('https://...')
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->columns(1)
                                                    ->defaultItems(0)
                                                    ->addActionLabel('Anbieter hinzufügen')
                                                    ->visible(fn ($get) => $get('mobility_options.car_rental.available') ?? false)
                                                    ->columnSpanFull(),
                                            ]),

                                        // ÖPNV
                                        \Filament\Schemas\Components\Fieldset::make('Öffentlicher Nahverkehr (ÖPNV)')
                                            ->schema([
                                                Toggle::make('mobility_options.public_transport.available')
                                                    ->label('Verfügbar')
                                                    ->default(false)
                                                    ->reactive()
                                                    ->columnSpanFull(),

                                                Repeater::make('mobility_options.public_transport.types')
                                                    ->label('Verkehrsmittel')
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('Verkehrsmittel')
                                                            ->required()
                                                            ->placeholder('z.B. S-Bahn, U-Bahn, Bus')
                                                            ->columnSpanFull(),

                                                        TextInput::make('url')
                                                            ->label('Info-URL')
                                                            ->url()
                                                            ->maxLength(2048)
                                                            ->placeholder('https://...')
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->columns(1)
                                                    ->defaultItems(0)
                                                    ->addActionLabel('Verkehrsmittel hinzufügen')
                                                    ->visible(fn ($get) => $get('mobility_options.public_transport.available') ?? false)
                                                    ->columnSpanFull(),
                                            ]),

                                        // Airport Shuttle
                                        \Filament\Schemas\Components\Fieldset::make('Airport Shuttle')
                                            ->schema([
                                                Toggle::make('mobility_options.airport_shuttle.available')
                                                    ->label('Verfügbar')
                                                    ->default(false)
                                                    ->reactive()
                                                    ->columnSpanFull(),

                                                Textarea::make('mobility_options.airport_shuttle.info')
                                                    ->label('Informationen')
                                                    ->rows(2)
                                                    ->placeholder('z.B. Kostenloser Shuttle zu Hotels, 24/7 Betrieb')
                                                    ->visible(fn ($get) => $get('mobility_options.airport_shuttle.available') ?? false)
                                                    ->columnSpanFull(),

                                                TextInput::make('mobility_options.airport_shuttle.url')
                                                    ->label('Info-URL')
                                                    ->url()
                                                    ->maxLength(2048)
                                                    ->placeholder('https://...')
                                                    ->visible(fn ($get) => $get('mobility_options.airport_shuttle.available') ?? false)
                                                    ->columnSpanFull(),
                                            ]),

                                        // Taxi
                                        \Filament\Schemas\Components\Fieldset::make('Taxi')
                                            ->schema([
                                                Toggle::make('mobility_options.taxi.available')
                                                    ->label('Verfügbar')
                                                    ->default(false)
                                                    ->reactive()
                                                    ->columnSpanFull(),

                                                Textarea::make('mobility_options.taxi.info')
                                                    ->label('Informationen')
                                                    ->rows(2)
                                                    ->placeholder('z.B. 24/7 verfügbar, Taxistand vor Terminal 1')
                                                    ->visible(fn ($get) => $get('mobility_options.taxi.available') ?? false)
                                                    ->columnSpanFull(),

                                                TextInput::make('mobility_options.taxi.approx_cost')
                                                    ->label('Ungefähre Kosten')
                                                    ->placeholder('z.B. 50 EUR in die Innenstadt')
                                                    ->visible(fn ($get) => $get('mobility_options.taxi.available') ?? false)
                                                    ->columnSpanFull(),
                                            ]),

                                        // Parkhäuser
                                        \Filament\Schemas\Components\Fieldset::make('Parkhäuser')
                                            ->schema([
                                                Toggle::make('mobility_options.parking.available')
                                                    ->label('Verfügbar')
                                                    ->default(false)
                                                    ->reactive()
                                                    ->columnSpanFull(),

                                                Repeater::make('mobility_options.parking.options')
                                                    ->label('Parkmöglichkeiten')
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('Name')
                                                            ->required()
                                                            ->placeholder('z.B. Parkhaus P1')
                                                            ->columnSpanFull(),

                                                        TextInput::make('distance')
                                                            ->label('Entfernung')
                                                            ->placeholder('z.B. 100m zum Terminal')
                                                            ->columnSpanFull(),

                                                        TextInput::make('price_info')
                                                            ->label('Preisinformation')
                                                            ->placeholder('z.B. 5 EUR/Tag')
                                                            ->columnSpanFull(),

                                                        TextInput::make('url')
                                                            ->label('Website/Buchungs-URL')
                                                            ->url()
                                                            ->maxLength(2048)
                                                            ->placeholder('https://...')
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->columns(1)
                                                    ->defaultItems(0)
                                                    ->addActionLabel('Parkmöglichkeit hinzufügen')
                                                    ->visible(fn ($get) => $get('mobility_options.parking.available') ?? false)
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        // Rechte Spalte - Grid 2 (Hotels)
                        \Filament\Schemas\Components\Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                // Hotels Section
                                Section::make('Hotels in der Nähe')
                                    ->description('Hotels in der Nähe des Flughafens')
                                    ->schema([
                                        Repeater::make('nearby_hotels')
                                            ->label('')
                                            ->schema([
                                                \Filament\Schemas\Components\Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('Name des Hotels')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->placeholder('z.B. Airport Hotel Frankfurt')
                                                            ->columnSpan(2),

                                                        TextInput::make('distance_km')
                                                            ->label('Entfernung (km)')
                                                            ->numeric()
                                                            ->step(0.1)
                                                            ->suffix('km')
                                                            ->placeholder('z.B. 0.5')
                                                            ->columnSpan(1),

                                                        Toggle::make('shuttle')
                                                            ->label('Shuttle-Service verfügbar')
                                                            ->default(false)
                                                            ->columnSpan(1),

                                                        TextInput::make('booking_url')
                                                            ->label('Buchungs-URL')
                                                            ->url()
                                                            ->maxLength(2048)
                                                            ->placeholder('https://...')
                                                            ->columnSpan(2),

                                                        Textarea::make('notes')
                                                            ->label('Zusätzliche Informationen')
                                                            ->rows(2)
                                                            ->maxLength(1000)
                                                            ->placeholder('z.B. Kostenloser Shuttle alle 30 Minuten')
                                                            ->columnSpan(2),
                                                    ]),
                                            ])
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                            ->addActionLabel('Hotel hinzufügen')
                                            ->reorderable(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ]),
            ]);
    }
}
