<?php

namespace App\Filament\Resources\Airports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
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
                TextColumn::make('city.name')
                    ->label('Stadt')
                    ->formatStateUsing(fn ($record) => $record->city?->getName('de'))
                    ->sortable(),
                TextColumn::make('country.name')
                    ->label('Land')
                    ->formatStateUsing(fn ($record) => $record->country?->getName('de'))
                    ->sortable(),
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
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('dst_timezone')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
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
