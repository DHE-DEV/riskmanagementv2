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

                                        TextInput::make('contact_info.help_url')
                                            ->label('Hilfe-URL')
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
                                                // Economy
                                                Fieldset::make('Economy')
                                                    ->columns(1)
                                                    ->schema([
                                                        TextInput::make('baggage_rules.hand_baggage.economy')
                                                            ->label('Gewicht')
                                                            ->placeholder('z.B. 1x8kg')
                                                            ->columnSpanFull(),

                                                        TextInput::make('baggage_rules.hand_baggage_dimensions.economy.length')
                                                            ->label('Länge (cm)')
                                                            ->numeric()
                                                            ->placeholder('z.B. 55')
                                                            ->columnSpanFull(),

                                                        TextInput::make('baggage_rules.hand_baggage_dimensions.economy.width')
                                                            ->label('Breite (cm)')
                                                            ->numeric()
                                                            ->placeholder('z.B. 40')
                                                            ->columnSpanFull(),

                                                        TextInput::make('baggage_rules.hand_baggage_dimensions.economy.height')
                                                            ->label('Höhe (cm)')
                                                            ->numeric()
                                                            ->placeholder('z.B. 23')
                                                            ->columnSpanFull(),
                                                    ]),

                                                // Premium Economy
                                                Fieldset::make('Premium Economy')
                                                    ->columns(1)
                                                    ->schema([
                                                        TextInput::make('baggage_rules.hand_baggage.premium_economy')
                                                            ->label('Gewicht')
                                                            ->placeholder('z.B. 2x8kg')
                                                            ->columnSpanFull(),

                                                        TextInput::make('baggage_rules.hand_baggage_dimensions.premium_economy.length')
                                                            ->label('Länge (cm)')
                                                            ->numeric()
                                                            ->placeholder('z.B. 55')
                                                            ->columnSpanFull(),

                                                        TextInput::make('baggage_rules.hand_baggage_dimensions.premium_economy.width')
                                                            ->label('Breite (cm)')
                                                            ->numeric()
                                                            ->placeholder('z.B. 40')
                                                            ->columnSpanFull(),

                                                        TextInput::make('baggage_rules.hand_baggage_dimensions.premium_economy.height')
                                                            ->label('Höhe (cm)')
                                                            ->numeric()
                                                            ->placeholder('z.B. 23')
                                                            ->columnSpanFull(),
                                                    ]),

                                                // Business Class
                                                Fieldset::make('Business Class')
                                                    ->columns(1)
                                                    ->schema([
                                                        TextInput::make('baggage_rules.hand_baggage.business')
                                                            ->label('Gewicht')
                                                            ->placeholder('z.B. 2x8kg')
                                                            ->columnSpanFull(),

                                                        TextInput::make('baggage_rules.hand_baggage_dimensions.business.length')
                                                            ->label('Länge (cm)')
                                                            ->numeric()
                                                            ->placeholder('z.B. 55')
                                                            ->columnSpanFull(),

                                                        TextInput::make('baggage_rules.hand_baggage_dimensions.business.width')
                                                            ->label('Breite (cm)')
                                                            ->numeric()
                                                            ->placeholder('z.B. 40')
                                                            ->columnSpanFull(),

                                                        TextInput::make('baggage_rules.hand_baggage_dimensions.business.height')
                                                            ->label('Höhe (cm)')
                                                            ->numeric()
                                                            ->placeholder('z.B. 23')
                                                            ->columnSpanFull(),
                                                    ]),

                                                // First Class
                                                Fieldset::make('First Class')
                                                    ->columns(1)
                                                    ->schema([
                                                        TextInput::make('baggage_rules.hand_baggage.first')
                                                            ->label('Gewicht')
                                                            ->placeholder('z.B. 2x8kg')
                                                            ->columnSpanFull(),

                                                        TextInput::make('baggage_rules.hand_baggage_dimensions.first.length')
                                                            ->label('Länge (cm)')
                                                            ->numeric()
                                                            ->placeholder('z.B. 55')
                                                            ->columnSpanFull(),

                                                        TextInput::make('baggage_rules.hand_baggage_dimensions.first.width')
                                                            ->label('Breite (cm)')
                                                            ->numeric()
                                                            ->placeholder('z.B. 40')
                                                            ->columnSpanFull(),

                                                        TextInput::make('baggage_rules.hand_baggage_dimensions.first.height')
                                                            ->label('Höhe (cm)')
                                                            ->numeric()
                                                            ->placeholder('z.B. 23')
                                                            ->columnSpanFull(),
                                                    ]),
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
                            ]),
                    ]),
            ]);
    }
}
