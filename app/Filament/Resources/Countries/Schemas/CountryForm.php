<?php

namespace App\Filament\Resources\Countries\Schemas;

use App\Models\Continent;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(2)
                    ->columns(2)
                    ->schema([
                        // Linke Spalte
                        Grid::make(1)
                            ->schema([
                                Section::make('Grunddaten')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('iso_code')
                                                    ->label('ISO 2-Code')
                                                    ->required()
                                                    ->maxLength(2)
                                                    ->unique(ignoreRecord: true),

                                                TextInput::make('iso3_code')
                                                    ->label('ISO 3-Code')
                                                    ->required()
                                                    ->maxLength(3)
                                                    ->unique(ignoreRecord: true),
                                            ]),

                                        KeyValue::make('name_translations')
                                            ->label('Namen (Übersetzungen)')
                                            ->keyLabel('Sprache')
                                            ->valueLabel('Name')
                                            ->default(['de' => '', 'en' => ''])
                                            ->required(),

                                        Select::make('continent_id')
                                            ->label('Kontinent')
                                            ->relationship('continent', 'code')
                                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name_translations['de'] ?? $record->name_translations['en'] ?? $record->code)
                                            ->required()
                                            ->searchable()
                                            ->preload(),

                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('is_eu_member')
                                                    ->label('EU-Mitglied')
                                                    ->default(false),

                                                Toggle::make('is_schengen_member')
                                                    ->label('Schengen-Mitglied')
                                                    ->default(false),
                                            ]),
                                    ]),

                                Section::make('Geokoordinaten')
                                    ->description(new \Illuminate\Support\HtmlString('Geben Sie die Koordinaten manuell ein oder fügen Sie einen <a href="https://www.google.com/maps" target="_blank" style="color: #3B82F6; text-decoration: underline; font-weight: 600;" onmouseover="this.style.color=\'#2563EB\'" onmouseout="this.style.color=\'#3B82F6\'">Google Maps</a> Link ein'))
                                    ->schema([
                                        Textarea::make('google_maps_import')
                                            ->label('Google Maps Import')
                                            ->placeholder('Fügen Sie hier kopierte Google Maps Koordinaten ein')
                                            ->rows(2)
                                            ->helperText('Nach dem Einfügen werden Breitengrad und Längengrad automatisch in die entsprechenden Felder übernommen')
                                            ->reactive()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                if (!$state) {
                                                    return;
                                                }

                                                $coordinates = self::parseGoogleMapsInput($state);

                                                if ($coordinates) {
                                                    // Stelle sicher, dass die Werte als Zahlen gesetzt werden
                                                    $set('lat', round($coordinates['lat'], 6));
                                                    $set('lng', round($coordinates['lng'], 6));
                                                    // Clear the import field after successful import
                                                    $set('google_maps_import', '');
                                                }
                                            }),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('lat')
                                                    ->label('Breitengrad')
                                                    ->numeric()
                                                    ->minValue(-90)
                                                    ->maxValue(90)
                                                    ->step('any')
                                                    ->placeholder('z.B. 51.5074')
                                                    ->reactive(),

                                                TextInput::make('lng')
                                                    ->label('Längengrad')
                                                    ->numeric()
                                                    ->minValue(-180)
                                                    ->maxValue(180)
                                                    ->step('any')
                                                    ->placeholder('z.B. -0.1278')
                                                    ->reactive(),
                                            ]),

                                        ViewField::make('map_link')
                                            ->label('')
                                            ->view('filament.forms.components.map-link')
                                            ->visible(fn ($get) => $get('lat') && $get('lng'))
                                            ->viewData(fn ($get) => [
                                                'lat' => $get('lat'),
                                                'lng' => $get('lng'),
                                            ]),
                                    ]),
                            ])
                            ->columnSpan(1),

                        // Rechte Spalte
                        Section::make('Weitere Informationen')
                            ->schema([
                                TextInput::make('currency_code')
                                    ->label('Währungscode')
                                    ->maxLength(3),

                                TextInput::make('currency_name')
                                    ->label('Währungsname')
                                    ->maxLength(255),

                                TextInput::make('currency_symbol')
                                    ->label('Währungssymbol')
                                    ->maxLength(10),

                                TextInput::make('phone_prefix')
                                    ->label('Telefonvorwahl')
                                    ->maxLength(10),

                                TextInput::make('timezone')
                                    ->label('Zeitzone')
                                    ->maxLength(255),

                                TextInput::make('population')
                                    ->label('Bevölkerung')
                                    ->numeric(),

                                TextInput::make('area_km2')
                                    ->label('Fläche (km²)')
                                    ->numeric(),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    /**
     * Parse Google Maps input to extract coordinates
     */
    private static function parseGoogleMapsInput(string $input): ?array
    {
        $input = trim($input);

        // Pattern 1: Direct coordinates like "51.5074, -0.1278"
        if (preg_match('/^(-?\d+\.?\d*)[,\s]+(-?\d+\.?\d*)$/', $input, $matches)) {
            return [
                'lat' => (float) $matches[1],
                'lng' => (float) $matches[2],
            ];
        }

        // Pattern 2: Google Maps URL with @coordinates
        if (preg_match('/@(-?\d+\.?\d*),(-?\d+\.?\d*)/', $input, $matches)) {
            return [
                'lat' => (float) $matches[1],
                'lng' => (float) $matches[2],
            ];
        }

        // Pattern 3: Google Maps place URL with ll= parameter
        if (preg_match('/ll=(-?\d+\.?\d*)[,\%2C]+(-?\d+\.?\d*)/', $input, $matches)) {
            return [
                'lat' => (float) $matches[1],
                'lng' => (float) $matches[2],
            ];
        }

        // Pattern 4: Google Maps search URL with query= parameter
        if (preg_match('/query=(-?\d+\.?\d*)[,\%2C]+(-?\d+\.?\d*)/', $input, $matches)) {
            return [
                'lat' => (float) $matches[1],
                'lng' => (float) $matches[2],
            ];
        }

        // Pattern 5: Google Maps place with /place/ format
        if (preg_match('/place\/.*?\/(-?\d+\.?\d*),(-?\d+\.?\d*)/', $input, $matches)) {
            return [
                'lat' => (float) $matches[1],
                'lng' => (float) $matches[2],
            ];
        }

        // Pattern 6: Coordinates in parentheses like "(51.5074, -0.1278)"
        if (preg_match('/\((-?\d+\.?\d*)[,\s]+(-?\d+\.?\d*)\)/', $input, $matches)) {
            return [
                'lat' => (float) $matches[1],
                'lng' => (float) $matches[2],
            ];
        }

        return null;
    }
}
