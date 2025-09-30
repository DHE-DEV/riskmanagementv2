<?php

namespace App\Filament\Resources\Continents\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ContinentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(10)
                    ->helperText('Eindeutiger Code für den Kontinent (z.B. EU für Europa)'),

                TextInput::make('sort_order')
                    ->label('Sortierung')
                    ->numeric()
                    ->default(0)
                    ->helperText('Niedrigere Werte werden zuerst angezeigt'),

                TextInput::make('name_translations.de')
                    ->label('Name (Deutsch)')
                    ->required()
                    ->maxLength(255),

                TextInput::make('name_translations.en')
                    ->label('Name (Englisch)')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Beschreibung')
                    ->maxLength(1000)
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('coordinates_import')
                    ->label('Google Maps Koordinaten')
                    ->placeholder('z.B. 48.1351, 11.5820 oder 48.1351,11.5820')
                    ->helperText('Koordinaten aus Google Maps einfügen (Format: Lat, Lng)')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (empty($state)) {
                            return;
                        }

                        $state = trim($state);
                        $state = str_replace([' ', "\t", "\n"], '', $state);

                        if (str_contains($state, ',')) {
                            $parts = explode(',', $state);
                            if (count($parts) >= 2) {
                                $lat = trim($parts[0]);
                                $lng = trim($parts[1]);

                                if (is_numeric($lat) && is_numeric($lng)) {
                                    $lat = floatval($lat);
                                    $lng = floatval($lng);

                                    if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                                        $set('lat', $lat);
                                        $set('lng', $lng);
                                    }
                                }
                            }
                        }
                    })
                    ->dehydrated(false),

                TextInput::make('lat')
                    ->label('Breitengrad')
                    ->numeric()
                    ->step(0.000001)
                    ->minValue(-90)
                    ->maxValue(90),

                TextInput::make('lng')
                    ->label('Längengrad')
                    ->numeric()
                    ->step(0.000001)
                    ->minValue(-180)
                    ->maxValue(180),
            ]);
    }
}
