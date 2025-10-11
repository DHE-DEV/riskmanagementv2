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
                                $modelType = $get('model_type');
                                if (!$modelType) {
                                    return 'Wählen Sie einen Model-Typ aus, um verfügbare Platzhalter zu sehen.';
                                }

                                $placeholders = match ($modelType) {
                                    'Country' => [
                                        'Allgemein' => ['{name}', '{name_en}', '{iso_code}', '{iso3_code}'],
                                        'Geografie' => ['{continent}', '{area_km2}'],
                                        'Mitgliedschaften' => ['{is_eu_member}', '{is_schengen_member}'],
                                        'Finanzen' => ['{currency_code}', '{currency_name}'],
                                        'Kontakt' => ['{phone_prefix}'],
                                        'Bevölkerung' => ['{population}'],
                                    ],
                                    'Continent' => [
                                        'Allgemein' => ['{name}', '{name_en}', '{code}', '{description}'],
                                        'Statistik' => ['{countries_count}'],
                                    ],
                                    'Region' => [
                                        'Allgemein' => ['{name}', '{name_en}', '{code}'],
                                        'Land' => ['{country}', '{country_en}'],
                                        'Details' => ['{description}', '{keywords}'],
                                        'Koordinaten' => ['{lat}', '{lng}'],
                                        'Statistik' => ['{cities_count}'],
                                    ],
                                    'City' => [
                                        'Allgemein' => ['{name}', '{name_en}'],
                                        'Zuordnung' => ['{country}', '{country_en}', '{country_code}', '{region}', '{region_en}'],
                                        'Details' => ['{population}', '{is_capital}', '{is_regional_capital}'],
                                        'Koordinaten' => ['{lat}', '{lng}'],
                                    ],
                                    'Airport' => [
                                        'Allgemein' => ['{name}'],
                                        'Codes' => ['{iata_code}', '{icao_code}'],
                                        'Zuordnung' => ['{city}', '{city_en}', '{country}', '{country_en}', '{country_code}'],
                                        'Details' => ['{timezone}', '{dst_timezone}', '{altitude}', '{type}'],
                                        'Koordinaten' => ['{lat}', '{lng}'],
                                    ],
                                    'CustomEvent' => [
                                        'Allgemein' => ['{title}', '{description}'],
                                        'Typ' => ['{event_type}', '{event_types}', '{priority}', '{severity}'],
                                        'Zeitraum' => ['{start_date}', '{end_date}'],
                                        'Status' => ['{is_active}', '{archived}'],
                                        'Zuordnung' => ['{countries}', '{data_source}'],
                                    ],
                                    'PassolutionEvent' => [
                                        'Allgemein' => ['{title}', '{description}', '{category}'],
                                        'Zeitraum' => ['{event_date}'],
                                        'Zuordnung' => ['{countries}', '{regions}', '{cities}'],
                                    ],
                                    default => []
                                };

                                if (empty($placeholders)) {
                                    return 'Keine Platzhalter verfügbar.';
                                }

                                $output = '<div class="text-sm"><strong>Verfügbare Platzhalter:</strong><br>';
                                foreach ($placeholders as $category => $items) {
                                    $output .= '<strong class="text-gray-700 dark:text-gray-300">' . $category . ':</strong> ';
                                    $output .= implode(', ', $items) . '<br>';
                                }
                                $output .= '</div>';

                                return new \Illuminate\Support\HtmlString($output);
                            }),
                    ]),
            ]);
    }
}
