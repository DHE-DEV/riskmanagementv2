<?php

namespace App\Filament\Resources\Countries\Schemas;

use App\Models\Continent;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                
                TextInput::make('code')
                    ->label('ISO 2-Code')
                    ->required()
                    ->maxLength(2)
                    ->unique(ignoreRecord: true),
                
                TextInput::make('iso3')
                    ->label('ISO 3-Code')
                    ->required()
                    ->maxLength(3)
                    ->unique(ignoreRecord: true),
                
                Select::make('continent_id')
                    ->label('Kontinent')
                    ->options(Continent::pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                
                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }
}
