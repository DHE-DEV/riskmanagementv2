<?php

namespace App\Filament\Resources\Regions\Schemas;

use App\Models\Country;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Ein eindeutiger Code für die Region (z.B. BY für Bayern)'),

                TextInput::make('name_translations.de')
                    ->label('Name (Deutsch)')
                    ->required()
                    ->maxLength(255),

                TextInput::make('name_translations.en')
                    ->label('Name (Englisch)')
                    ->maxLength(255),

                Select::make('country_id')
                    ->label('Land')
                    ->options(function () {
                        return Country::all()->mapWithKeys(function ($country) {
                            return [$country->id => $country->getName('de')];
                        })->toArray();
                    })
                    ->required()
                    ->searchable(),

                Textarea::make('description')
                    ->label('Beschreibung')
                    ->maxLength(1000)
                    ->rows(3),

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

                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }
}
