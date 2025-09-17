<?php

namespace App\Filament\Resources\InfosystemEntries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InfosystemEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('api_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('position')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('appearance')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('country_code')
                    ->searchable(),
                TextColumn::make('lang')
                    ->searchable(),
                TextColumn::make('language_content')
                    ->searchable(),
                TextColumn::make('language_code')
                    ->searchable(),
                TextColumn::make('tagtype')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tagtext')
                    ->searchable(),
                TextColumn::make('tagdate')
                    ->date()
                    ->sortable(),
                TextColumn::make('header')
                    ->searchable(),
                IconColumn::make('archive')
                    ->boolean(),
                IconColumn::make('is_published')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('published_as_event_id')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('active')
                    ->boolean(),
                TextColumn::make('api_created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('request_id')
                    ->searchable(),
                TextColumn::make('response_time')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
