<?php

namespace App\Filament\Resources\CustomEvents\Tables;

use App\Models\CustomEvent;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                
                TextColumn::make('event_type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => CustomEvent::getEventTypeOptions()[$state] ?? $state),
                
                TextColumn::make('category')
                    ->label('Kategorie')
                    ->searchable(),
                
                TextColumn::make('priority')
                    ->label('Priorität')
                    ->formatStateUsing(fn (string $state): string => CustomEvent::getPriorityOptions()[$state] ?? $state),
                
                TextColumn::make('start_date')
                    ->label('Start')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                
                TextColumn::make('end_date')
                    ->label('Ende')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                
                TextColumn::make('is_active')
                    ->label('Aktiv')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ja' : 'Nein'),
                
                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                
                SelectFilter::make('event_type')
                    ->label('Event-Typ')
                    ->options(CustomEvent::getEventTypeOptions())
                    ->multiple(),
                
                SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options(CustomEvent::getPriorityOptions())
                    ->multiple(),
                
                TernaryFilter::make('is_active')
                    ->label('Nur aktive Events')
                    ->placeholder('Alle Events')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Anzeigen'),
                EditAction::make()
                    ->label('Bearbeiten'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Löschen'),
                    ForceDeleteBulkAction::make()
                        ->label('Endgültig löschen'),
                    RestoreBulkAction::make()
                        ->label('Wiederherstellen'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
