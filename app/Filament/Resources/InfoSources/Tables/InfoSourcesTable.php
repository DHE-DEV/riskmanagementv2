<?php

namespace App\Filament\Resources\InfoSources\Tables;

use App\Services\FeedFetcherService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class InfoSourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                IconColumn::make('is_active')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->width(40),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->description(fn ($record) => $record->description ? \Str::limit($record->description, 60) : null),

                TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'rss' => 'RSS',
                        'api' => 'API',
                        'rss_api' => 'RSS+API',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'rss' => 'warning',
                        'api' => 'info',
                        'rss_api' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('content_type')
                    ->label('Inhalt')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'travel_advisory' => 'Reisewarnungen',
                        'health' => 'Gesundheit',
                        'disaster' => 'Katastrophen',
                        'conflict' => 'Konflikte',
                        'general' => 'Allgemein',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'travel_advisory' => 'danger',
                        'health' => 'success',
                        'disaster' => 'warning',
                        'conflict' => 'danger',
                        'general' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('country_code')
                    ->label('Land')
                    ->formatStateUsing(fn (?string $state): string => $state ?? 'INT')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('language')
                    ->label('Sprache')
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('refresh_interval')
                    ->label('Intervall')
                    ->formatStateUsing(function (int $state): string {
                        if ($state < 60) return $state . 's';
                        if ($state < 3600) return round($state / 60) . 'm';
                        return round($state / 3600) . 'h';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('items_count')
                    ->label('Einträge')
                    ->counts('items')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('last_fetched_at')
                    ->label('Letzter Abruf')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Nie'),

                IconColumn::make('has_error')
                    ->label('Status')
                    ->state(fn ($record) => $record->hasError())
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn ($record) => $record->last_error_message),

                IconColumn::make('auto_import')
                    ->label('Auto')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-hand-raised')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->auto_import ? 'Auto-Import aktiv' : 'Manueller Import')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),

                SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'rss' => 'RSS Feed',
                        'api' => 'JSON API',
                        'rss_api' => 'RSS + API',
                    ]),

                SelectFilter::make('content_type')
                    ->label('Inhaltstyp')
                    ->options([
                        'travel_advisory' => 'Reisewarnungen',
                        'health' => 'Gesundheit',
                        'disaster' => 'Naturkatastrophen',
                        'conflict' => 'Konflikte & Unruhen',
                        'general' => 'Allgemein',
                    ]),
            ])
            ->recordActions([
                Action::make('toggle_active')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn ($record) => $record->is_active ? 'warning' : 'success')
                    ->tooltip(fn ($record) => $record->is_active ? 'Deaktivieren' : 'Aktivieren')
                    ->iconButton()
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                    }),
                Action::make('fetch_now')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->tooltip('Jetzt abrufen')
                    ->iconButton()
                    ->action(function ($record) {
                        $fetcher = app(FeedFetcherService::class);
                        $stats = $fetcher->fetch($record);

                        if ($stats['errors'] > 0) {
                            Notification::make()
                                ->title('Fehler beim Abruf')
                                ->body($record->last_error_message)
                                ->danger()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Feed abgerufen')
                                ->body("Neu: {$stats['new']}, Aktualisiert: {$stats['updated']}")
                                ->success()
                                ->send();
                        }
                    }),
                Action::make('view_items')
                    ->icon('heroicon-o-newspaper')
                    ->color('gray')
                    ->tooltip('Einträge anzeigen')
                    ->iconButton()
                    ->url(fn ($record) => route('filament.admin.resources.info-source-items.index', ['tableFilters[info_source_id][value]' => $record->id])),
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
                    BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
