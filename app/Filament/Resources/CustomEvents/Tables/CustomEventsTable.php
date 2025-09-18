<?php

namespace App\Filament\Resources\CustomEvents\Tables;

use App\Models\CustomEvent;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
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
                
                TextColumn::make('eventTypes.name')
                    ->label('Typen')
                    ->badge()
                    ->separator(', ')
                    ->searchable()
                    ->placeholder('Nicht zugeordnet'),

                TextColumn::make('category')
                    ->label('Kategorie')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ja' : 'Nein')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                TextColumn::make('archived')
                    ->label('Archiviert')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ja' : 'Nein')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('archived_at')
                    ->label('Archiviert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                
                SelectFilter::make('event_type_id')
                    ->label('Event-Typ')
                    ->relationship('eventType', 'name')
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

                TernaryFilter::make('archived')
                    ->label('Archivierte Events')
                    ->placeholder('Alle Events')
                    ->trueLabel('Nur archivierte')
                    ->falseLabel('Nur nicht-archivierte'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Anzeigen'),
                EditAction::make()
                    ->label('Bearbeiten'),
                Action::make('duplicate')
                    ->label('Duplizieren')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (CustomEvent $record) {
                        $newEvent = $record->replicate();
                        $newEvent->title = $record->title . ' (Kopie)';
                        $newEvent->created_by = auth()->id();
                        $newEvent->updated_by = auth()->id();
                        $newEvent->save();
                        
                        return redirect()->route('filament.admin.resources.custom-events.edit', $newEvent);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Event duplizieren')
                    ->modalSubheading('Möchten Sie dieses Event wirklich duplizieren?'),
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
