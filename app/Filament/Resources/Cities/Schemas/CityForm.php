<?php

namespace App\Filament\Resources\Cities\Schemas;

use App\Models\Country;
use App\Models\Region;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('region_id', null)),

                Select::make('region_id')
                    ->label('Region')
                    ->options(function (Get $get) {
                        $countryId = $get('country_id');
                        if (!$countryId) {
                            return [];
                        }
                        return Region::where('country_id', $countryId)->get()->mapWithKeys(function ($region) {
                            return [$region->id => $region->getName('de')];
                        })->toArray();
                    })
                    ->getSearchResultsUsing(function (string $search, Get $get) {
                        $countryId = $get('country_id');
                        if (!$countryId) {
                            return [];
                        }
                        return Region::where('country_id', $countryId)
                            ->where(function ($query) use ($search) {
                                $query->where('code', 'like', "%{$search}%")
                                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))) LIKE LOWER(?)", ["%{$search}%"])
                                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en'))) LIKE LOWER(?)", ["%{$search}%"]);
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($region) => [$region->id => $region->getName('de')])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),

                Toggle::make('is_capital')
                    ->label('Hauptstadt')
                    ->default(false),

                Toggle::make('is_regional_capital')
                    ->label('Region-Hauptstadt')
                    ->default(false),

                TextInput::make('population')
                    ->label('Bevölkerung')
                    ->numeric(),

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
