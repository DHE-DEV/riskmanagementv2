<?php

namespace App\Filament\Resources\InfosystemEntries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class InfosystemEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('api_id')
                    ->required()
                    ->numeric(),
                TextInput::make('position')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('appearance')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('country_code')
                    ->required(),
                TextInput::make('country_names')
                    ->required(),
                TextInput::make('lang')
                    ->required()
                    ->default('de'),
                TextInput::make('language_content'),
                TextInput::make('language_code'),
                TextInput::make('tagtype')
                    ->numeric(),
                TextInput::make('tagtext'),
                DatePicker::make('tagdate')
                    ->required(),
                TextInput::make('header')
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('archive')
                    ->required(),
                Toggle::make('is_published')
                    ->required(),
                DateTimePicker::make('published_at'),
                TextInput::make('published_as_event_id')
                    ->numeric(),
                Toggle::make('active')
                    ->required()
                    ->default(true),
                DateTimePicker::make('api_created_at'),
                TextInput::make('request_id'),
                TextInput::make('response_time')
                    ->numeric(),
            ]);
    }
}
