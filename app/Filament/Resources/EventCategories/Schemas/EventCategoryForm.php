<?php

namespace App\Filament\Resources\EventCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EventCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_type_id')
                    ->relationship('eventType', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('description'),
                TextInput::make('color'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
