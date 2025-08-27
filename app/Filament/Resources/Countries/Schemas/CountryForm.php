<?php

namespace App\Filament\Resources\Countries\Schemas;

use App\Models\Continent;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                
                Toggle::make('is_eu_member')
                    ->label('EU-Mitglied')
                    ->default(false),

                Toggle::make('is_schengen_member')
                    ->label('Schengen-Mitglied')
                    ->default(false),

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

                TextInput::make('lat')
                    ->label('Breitengrad')
                    ->numeric(),

                TextInput::make('lng')
                    ->label('Längengrad')
                    ->numeric(),
            ]);
    }
}
