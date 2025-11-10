<?php

namespace App\Filament\Resources\Airlines\Schemas;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Country;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AirlineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Erstes Grid - Allgemeine Informationen
                Grid::make(2)
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 2,
                    ])
                    ->schema([
                        // Linke Spalte
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name der Airline')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('z.B. Lufthansa'),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('iata_code')
                                            ->label('IATA Code')
                                            ->maxLength(2)
                                            ->placeholder('z.B. LH')
                                            ->unique(ignoreRecord: true),

                                        TextInput::make('icao_code')
                                            ->label('ICAO Code')
                                            ->maxLength(3)
                                            ->placeholder('z.B. DLH')
                                            ->unique(ignoreRecord: true),
                                    ]),

                                Select::make('home_country_id')
                                    ->label('Heimatland')
                                    ->options(function () {
                                        return Country::all()->mapWithKeys(function ($country) {
                                            return [$country->id => $country->getName('de')];
                                        })->toArray();
                                    })
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('headquarters')
                                    ->label('Hauptsitz')
                                    ->maxLength(255)
                                    ->placeholder('z.B. Frankfurt am Main'),

                                Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true),
                            ]),

                        // Rechte Spalte
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                TextInput::make('website')
                                    ->label('Website')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://www.airline.com'),

                                TextInput::make('booking_url')
                                    ->label('Buchungslink')
                                    ->url()
                                    ->maxLength(255)
                                    ->placeholder('https://www.airline.com/booking'),

                                // Kontaktmöglichkeiten
                                Fieldset::make('Kontaktmöglichkeiten')
                                    ->schema([
                                        TextInput::make('contact_info.hotline')
                                            ->label('Hotline')
                                            ->tel()
                                            ->placeholder('+49 123 456789'),

                                        TextInput::make('contact_info.email')
                                            ->label('E-Mail')
                                            ->email()
                                            ->placeholder('kontakt@airline.com'),

                                        TextInput::make('contact_info.chat_url')
                                            ->label('Chat-URL')
                                            ->url()
                                            ->placeholder('https://...'),
                                    ]),
                            ]),
                    ]),

                // Zweites Grid - Service & Spezielle Services
                Grid::make(2)
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 2,
                    ])
                    ->schema([
                        // Linke Spalte
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                // Tarifarten Section
                                Section::make('Tarifarten / Kabinenklassen')
                                    ->description('Welche Kabinenklassen bietet diese Airline an?')
                                    ->schema([
                                        CheckboxList::make('cabin_classes')
                                            ->label('')
                                            ->options(Airline::getCabinClassOptions())
                                            ->columns(2)
                                            ->gridDirection('row'),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false),

                                // Freigepäck & Handgepäck Section
                                Section::make('Freigepäck & Handgepäck')
                                    ->description('Gepäckregelungen nach Kabinenklasse')
                                    ->schema([
                                        Fieldset::make('Freigepäck (Aufgabegepäck)')
                                            ->schema([
                                                TextInput::make('baggage_rules.checked_baggage.economy')
                                                    ->label('Economy')
                                                    ->placeholder('z.B. 1x23kg'),

                                                TextInput::make('baggage_rules.checked_baggage.premium_economy')
                                                    ->label('Premium Economy')
                                                    ->placeholder('z.B. 2x23kg'),

                                                TextInput::make('baggage_rules.checked_baggage.business')
                                                    ->label('Business Class')
                                                    ->placeholder('z.B. 2x32kg'),

                                                TextInput::make('baggage_rules.checked_baggage.first')
                                                    ->label('First Class')
                                                    ->placeholder('z.B. 3x32kg'),
                                            ]),

                                        Fieldset::make('Handgepäck')
                                            ->schema([
                                                TextInput::make('baggage_rules.hand_baggage.economy')
                                                    ->label('Economy')
                                                    ->placeholder('z.B. 1x8kg'),

                                                TextInput::make('baggage_rules.hand_baggage.premium_economy')
                                                    ->label('Premium Economy')
                                                    ->placeholder('z.B. 2x8kg'),

                                                TextInput::make('baggage_rules.hand_baggage.business')
                                                    ->label('Business Class')
                                                    ->placeholder('z.B. 2x8kg'),

                                                TextInput::make('baggage_rules.hand_baggage.first')
                                                    ->label('First Class')
                                                    ->placeholder('z.B. 2x8kg'),
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        // Rechte Spalte
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                // Haustiermitnahme Section
                                Section::make('Haustiermitnahme')
                                    ->description('Regelungen für die Mitnahme von Haustieren')
                                    ->schema([
                                        Toggle::make('pet_policy.allowed')
                                            ->label('Haustiermitnahme erlaubt')
                                            ->default(false)
                                            ->reactive(),

                                        Fieldset::make('In der Kabine')
                                            ->schema([
                                                TextInput::make('pet_policy.in_cabin.max_weight')
                                                    ->label('Maximales Gewicht')
                                                    ->placeholder('z.B. 8kg'),

                                                TextInput::make('pet_policy.in_cabin.carrier_size')
                                                    ->label('Transportbox-Größe')
                                                    ->placeholder('z.B. 55x40x23cm'),
                                            ])
                                            ->visible(fn ($get) => $get('pet_policy.allowed') ?? false),

                                        Fieldset::make('Im Frachtraum')
                                            ->schema([
                                                TextInput::make('pet_policy.in_hold.max_weight')
                                                    ->label('Maximales Gewicht')
                                                    ->placeholder('z.B. 75kg'),

                                                Textarea::make('pet_policy.in_hold.notes')
                                                    ->label('Zusätzliche Hinweise')
                                                    ->rows(2)
                                                    ->placeholder('z.B. Nur bestimmte Rassen'),
                                            ])
                                            ->visible(fn ($get) => $get('pet_policy.allowed') ?? false),

                                        TextInput::make('pet_policy.info_url')
                                            ->label('Info-URL')
                                            ->url()
                                            ->placeholder('https://...')
                                            ->visible(fn ($get) => $get('pet_policy.allowed') ?? false),

                                        Textarea::make('pet_policy.notes')
                                            ->label('Allgemeine Hinweise')
                                            ->rows(3)
                                            ->placeholder('Weitere Informationen zur Haustiermitnahme')
                                            ->visible(fn ($get) => $get('pet_policy.allowed') ?? false),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),

                                // Lounges Section
                                Section::make('Lounges & Zugänge')
                                    ->description('Lounge-Angebote und Zugangsregelungen')
                                    ->schema([
                                        Repeater::make('lounges')
                                            ->label('')
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('name')
                                                            ->label('Name der Lounge')
                                                            ->required()
                                                            ->placeholder('z.B. Lufthansa Business Lounge')
                                                            ->columnSpan(2),

                                                        TextInput::make('location')
                                                            ->label('Standort/Flughafen')
                                                            ->placeholder('z.B. Frankfurt Terminal 1')
                                                            ->columnSpan(1),

                                                        TextInput::make('access')
                                                            ->label('Zugang')
                                                            ->placeholder('z.B. Business Class, HON Circle')
                                                            ->columnSpan(1),

                                                        TextInput::make('url')
                                                            ->label('Info-URL')
                                                            ->url()
                                                            ->placeholder('https://...')
                                                            ->columnSpan(2),
                                                    ]),
                                            ])
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                            ->addActionLabel('Lounge hinzufügen')
                                            ->reorderable(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ]),

                // Direktverbindungen Section
                Section::make('Direktverbindungen')
                    ->description('Flughäfen, die von dieser Airline direkt angeflogen werden')
                    ->schema([
                        Select::make('airports')
                            ->label('Flughäfen mit Direktverbindungen')
                            ->relationship('airports', 'name')
                            ->options(function () {
                                return Airport::all()->mapWithKeys(function ($airport) {
                                    return [$airport->id => $airport->name . ' (' . $airport->iata_code . ')'];
                                })->toArray();
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Wählen Sie alle Flughäfen aus, zu/von denen diese Airline Direktflüge anbietet'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
