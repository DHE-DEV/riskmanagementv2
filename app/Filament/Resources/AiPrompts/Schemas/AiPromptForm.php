<?php

namespace App\Filament\Resources\AiPrompts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AiPromptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Allgemeine Informationen')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name der Aufgabe')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('z.B. Risikobewertung erstellen')
                                    ->columnSpan(1),

                                Select::make('model_type')
                                    ->label('Model-Typ')
                                    ->options([
                                        'Country' => 'Länder',
                                        'Continent' => 'Kontinente',
                                        'Region' => 'Regionen',
                                        'City' => 'Städte',
                                        'Airport' => 'Flughäfen',
                                        'CustomEvent' => 'Benutzerdefinierte Events',
                                        'PassolutionEvent' => 'Passolution Events',
                                    ])
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->columnSpan(1),
                            ]),

                        Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Kurze Beschreibung der Aufgabe'),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('category')
                                    ->label('Kategorie')
                                    ->maxLength(255)
                                    ->placeholder('z.B. Sicherheit, Wirtschaft')
                                    ->columnSpan(1),

                                TextInput::make('sort_order')
                                    ->label('Sortierung')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->columnSpan(1),

                                Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->default(true)
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Prompt Template')
                    ->description('Verwenden Sie Platzhalter in geschweiften Klammern, z.B. {name}, {iso_code}, {continent}')
                    ->schema([
                        Textarea::make('prompt_template')
                            ->label('Prompt-Vorlage')
                            ->required()
                            ->rows(10)
                            ->placeholder(function ($get) {
                                return match ($get('model_type')) {
                                    'Country' => "Beispiel für Länder:\n\nErstelle eine detaillierte Risikobewertung für {name}.\n\nLänderdaten:\n- ISO-Code: {iso_code}\n- Kontinent: {continent}\n- EU-Mitglied: {is_eu_member}\n- Bevölkerung: {population}\n\nBitte analysiere politische, wirtschaftliche und soziale Risiken.",
                                    'Continent' => "Beispiel für Kontinente:\n\nErstelle eine Übersicht für den Kontinent {name}.\n\nKontinentdaten:\n- Code: {code}\n- Beschreibung: {description}",
                                    'Region' => "Beispiel für Regionen:\n\nAnalysiere die Region {name}.\n\nRegionsdaten:\n- Land: {country}\n- Code: {code}",
                                    'City' => "Beispiel für Städte:\n\nErstelle Informationen für {name}.\n\nStadtdaten:\n- Land: {country}\n- Bevölkerung: {population}\n- Ist Hauptstadt: {is_capital}",
                                    'Airport' => "Beispiel für Flughäfen:\n\nAnalysiere den Flughafen {name}.\n\nFlughafendaten:\n- IATA-Code: {iata_code}\n- ICAO-Code: {icao_code}\n- Stadt: {city}\n- Land: {country}",
                                    'CustomEvent' => "Beispiel für Benutzerdefinierte Events:\n\nAnalysiere das Event {title}.\n\nEvent-Daten:\n- Beschreibung: {description}\n- Typ: {event_type}\n- Risiko-Level: {risk_level}\n- Start: {start_date}\n- Ende: {end_date}",
                                    'PassolutionEvent' => "Beispiel für Passolution Events:\n\nAnalysiere das Event {title}.\n\nEvent-Daten:\n- Beschreibung: {description}\n- Kategorie: {category}\n- Länder: {countries}",
                                    default => "Wählen Sie einen Model-Typ aus, um ein Beispiel zu sehen."
                                };
                            })
                            ->helperText(function ($get) {
                                return match ($get('model_type')) {
                                    'Country' => 'Verfügbare Platzhalter: {name}, {name_en}, {iso_code}, {iso3_code}, {continent}, {is_eu_member}, {is_schengen_member}, {currency_code}, {currency_name}, {phone_prefix}, {population}, {area_km2}',
                                    'Continent' => 'Verfügbare Platzhalter: {name}, {name_en}, {code}, {description}',
                                    'Region' => 'Verfügbare Platzhalter: {name}, {name_en}, {code}, {country}, {country_code}',
                                    'City' => 'Verfügbare Platzhalter: {name}, {name_en}, {country}, {country_code}, {region}, {population}, {is_capital}, {timezone}',
                                    'Airport' => 'Verfügbare Platzhalter: {name}, {name_en}, {iata_code}, {icao_code}, {city}, {country}, {country_code}, {timezone}, {elevation}',
                                    'CustomEvent' => 'Verfügbare Platzhalter: {title}, {description}, {event_type}, {risk_level}, {start_date}, {end_date}, {is_active}, {archived}, {countries}',
                                    'PassolutionEvent' => 'Verfügbare Platzhalter: {title}, {description}, {category}, {event_date}, {countries}, {regions}, {cities}',
                                    default => 'Wählen Sie einen Model-Typ aus, um verfügbare Platzhalter zu sehen.'
                                };
                            }),
                    ]),
            ]);
    }
}
