<?php

namespace App\Filament\Resources\AiPrompts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AiPromptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->description)
                    ->wrap(),

                TextColumn::make('model_type')
                    ->label('Model-Typ')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Country' => 'Länder',
                        'Continent' => 'Kontinente',
                        'Region' => 'Regionen',
                        'City' => 'Städte',
                        'Airport' => 'Flughäfen',
                        'CustomEvent' => 'Custom Events',
                        'PassolutionEvent' => 'Passolution Events',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Country' => 'success',
                        'Continent' => 'primary',
                        'Region' => 'info',
                        'City' => 'warning',
                        'Airport' => 'danger',
                        'CustomEvent' => 'gray',
                        'PassolutionEvent' => 'purple',
                        default => 'gray',
                    }),

                TextColumn::make('category')
                    ->label('Kategorie')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Keine Kategorie'),

                TextColumn::make('sort_order')
                    ->label('Sortierung')
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Aktualisiert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                SelectFilter::make('model_type')
                    ->label('Model-Typ')
                    ->options([
                        'Country' => 'Länder',
                        'Continent' => 'Kontinente',
                        'Region' => 'Regionen',
                        'City' => 'Städte',
                        'Airport' => 'Flughäfen',
                        'CustomEvent' => 'Custom Events',
                        'PassolutionEvent' => 'Passolution Events',
                    ]),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktiv',
                        '0' => 'Inaktiv',
                    ]),

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
