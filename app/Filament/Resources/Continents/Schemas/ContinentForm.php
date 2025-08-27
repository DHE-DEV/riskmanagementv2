<?php

namespace App\Filament\Resources\Continents\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ContinentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                KeyValue::make('name_translations')
                    ->label('Namen (Übersetzungen)')
                    ->keyLabel('Sprache')
                    ->valueLabel('Name')
                    ->default(['de' => '', 'en' => ''])
                    ->required(),
                
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->maxLength(10),
                    
                Textarea::make('description')
                    ->label('Beschreibung')
                    ->columnSpanFull(),
                    
                KeyValue::make('keywords')
                    ->label('Schlagwörter')
                    ->keyLabel('Index')
                    ->valueLabel('Schlagwort')
                    ->columnSpanFull(),
                    
                TextInput::make('lat')
                    ->label('Breitengrad')
                    ->numeric(),
                    
                TextInput::make('lng')
                    ->label('Längengrad')
                    ->numeric(),
            ]);
    }
}
