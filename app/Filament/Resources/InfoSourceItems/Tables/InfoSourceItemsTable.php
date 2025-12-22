<?php

namespace App\Filament\Resources\InfoSourceItems\Tables;

use App\Models\InfoSource;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class InfoSourceItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('infoSource.name')
                    ->label('Quelle')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->limit(60)
                    ->formatStateUsing(fn ($state) => strip_tags($state))
                    ->tooltip(fn ($record) => strip_tags($record->title))
                    ->url(fn ($record) => $record->link, shouldOpenInNewTab: true),

                TextColumn::make('description')
                    ->label('Beschreibung')
                    ->searchable()
                    ->limit(80)
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => strip_tags($state))
                    ->tooltip(fn ($record) => strip_tags(\Str::limit($record->description, 300))),

                TextColumn::make('categories')
                    ->label('Kategorien')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', array_slice($state, 0, 3)) : '')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('published_at')
                    ->label('Veröffentlicht')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at_source')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->color(fn ($record) => $record->updated_at_source && $record->updated_at_source->isToday() ? 'warning' : null)
                    ->weight(fn ($record) => $record->updated_at_source && $record->updated_at_source->isToday() ? 'bold' : null),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'new' => 'Neu',
                        'reviewed' => 'Geprüft',
                        'imported' => 'Importiert',
                        'ignored' => 'Ignoriert',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'reviewed' => 'warning',
                        'imported' => 'success',
                        'ignored' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Abgerufen')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('info_source_id')
                    ->label('Quelle')
                    ->options(fn () => InfoSource::ordered()->pluck('name', 'id'))
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'new' => 'Neu',
                        'reviewed' => 'Geprüft',
                        'imported' => 'Importiert',
                        'ignored' => 'Ignoriert',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton(),
                Action::make('open_link')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->tooltip('Link öffnen')
                    ->iconButton()
                    ->url(fn ($record) => $record->link, shouldOpenInNewTab: true)
                    ->visible(fn ($record) => !empty($record->link)),
                Action::make('mark_reviewed')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->tooltip('Als geprüft markieren')
                    ->iconButton()
                    ->visible(fn ($record) => $record->status === 'new')
                    ->action(fn ($record) => $record->markAsReviewed()),
                Action::make('ignore')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->tooltip('Ignorieren')
                    ->iconButton()
                    ->visible(fn ($record) => in_array($record->status, ['new', 'reviewed']))
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->markAsIgnored()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_reviewed')
                        ->label('Als geprüft markieren')
                        ->icon('heroicon-o-eye')
                        ->color('warning')
                        ->action(fn (Collection $records) => $records->each->markAsReviewed()),
                    BulkAction::make('ignore')
                        ->label('Ignorieren')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->markAsIgnored()),
                ]),
            ])
            ->defaultSort('published_at', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}
