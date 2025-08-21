<?php

namespace App\Filament\Resources\Cities\Schemas;

use App\Models\Country;
use App\Models\Region;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                
                Select::make('country_id')
                    ->label('Land')
                    ->options(Country::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->form->fill(['region_id' => null])),
                
                Select::make('region_id')
                    ->label('Region')
                    ->options(function (Get $get) {
                        $countryId = $get('country_id');
                        if (!$countryId) {
                            return [];
                        }
                        return Region::where('country_id', $countryId)->pluck('name', 'id');
                    })
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
