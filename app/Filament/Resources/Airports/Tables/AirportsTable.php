<?php

namespace App\Filament\Resources\Airports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AirportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('iata_code')
                    ->searchable(),
                TextColumn::make('icao_code')
                    ->searchable(),
                TextColumn::make('city_name')
                    ->label('Stadt')
                    ->state(fn ($record) => $record->city?->getName('de'))
                    ->searchable()
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('city_id', $direction);
                    }),
                TextColumn::make('country_name')
                    ->label('Land')
                    ->state(fn ($record) => $record->country?->getName('de'))
                    ->searchable()
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('country_id', $direction);
                    }),
                TextColumn::make('lat')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lng')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('altitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('timezone')
                    ->searchable(),
                TextColumn::make('dst_timezone')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('source')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
