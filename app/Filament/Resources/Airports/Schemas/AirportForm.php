<?php

namespace App\Filament\Resources\Airports\Schemas;

use App\Models\City;
use App\Models\Country;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AirportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                
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
                
                Select::make('type')
                    ->label('Typ')
                    ->options([
                        'domestic' => 'Inland',
                        'international' => 'International',
                        'military' => 'Militär',
                    ])
                    ->default('domestic')
                    ->required(),
                
                Select::make('country_id')
                    ->label('Land')
                    ->options(Country::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->form->fill(['city_id' => null])),
                
                Select::make('city_id')
                    ->label('Stadt')
                    ->options(function (Get $get) {
                        $countryId = $get('country_id');
                        if (!$countryId) {
                            return [];
                        }
                        return City::where('country_id', $countryId)->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable(),
                
                TextInput::make('latitude')
                    ->label('Breitengrad')
                    ->numeric()
                    ->step(0.000001),
                
                TextInput::make('longitude')
                    ->label('Längengrad')
                    ->numeric()
                    ->step(0.000001),
                
                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }
}
