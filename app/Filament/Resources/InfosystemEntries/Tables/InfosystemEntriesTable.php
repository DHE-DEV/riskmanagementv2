<?php

namespace App\Filament\Resources\InfosystemEntries\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InfosystemEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tagdate')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('country_code')
                    ->label('Land')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state, $record): string => $state.' - '.($record->getCountryName('de') ?? $record->country_code)),

                TextColumn::make('header')
                    ->label('Titel')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                TextColumn::make('tagtext')
                    ->label('Tag')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sicherheit' => 'danger',
                        'Gesundheit' => 'warning',
                        'Reise' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('lang')
                    ->label('Sprache')
                    ->badge()
                    ->color('success')
                    ->searchable(),

                IconColumn::make('active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('archive')
                    ->label('Archiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-archive-box')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('api_id')
                    ->label('API ID')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Importiert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('tagdate', 'desc')
            ->filters([
                SelectFilter::make('country_code')
                    ->label('Land')
                    ->options(function () {
                        return \App\Models\InfosystemEntry::query()
                            ->distinct()
                            ->pluck('country_code', 'country_code')
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),

                SelectFilter::make('lang')
                    ->label('Sprache')
                    ->options([
                        'de' => 'Deutsch',
                        'en' => 'Englisch',
                        'fr' => 'Französisch',
                        'es' => 'Spanisch',
                    ]),

                TernaryFilter::make('active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive')
                    ->queries(
                        true: fn (Builder $query) => $query->where('active', true),
                        false: fn (Builder $query) => $query->where('active', false),
                    ),

                TernaryFilter::make('archive')
                    ->label('Archiv')
                    ->placeholder('Alle')
                    ->trueLabel('Nur archivierte')
                    ->falseLabel('Nur nicht archivierte')
                    ->queries(
                        true: fn (Builder $query) => $query->where('archive', true),
                        false: fn (Builder $query) => $query->where('archive', false),
                    ),

                Filter::make('tagdate')
                    ->form([
                        DatePicker::make('from')
                            ->label('Von Datum'),
                        DatePicker::make('until')
                            ->label('Bis Datum'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tagdate', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tagdate', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Von: '.\Carbon\Carbon::parse($data['from'])->format('d.m.Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Bis: '.\Carbon\Carbon::parse($data['until'])->format('d.m.Y');
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Details'),
                Action::make('createEvent')
                    ->label('Event anlegen')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(function ($record): string {
                        $params = [
                            'country_code' => $record->country_code,
                            'event_date' => $record->tagdate->format('Y-m-d'),
                            'title' => $record->header,
                            'description' => $record->content,
                            'source' => 'Infosystem (API ID: '.$record->api_id.')',
                            'severity' => self::mapTagTypeToSeverity($record->tagtype ?? 1),
                            'is_active' => $record->active ? 1 : 0,
                        ];

                        return '/admin/custom-events/create?'.http_build_query($params);
                    })
                    ->openUrlInNewTab(false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->searchPlaceholder('Suche nach Titel, Land, Tag...')
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->poll('60s');
    }

    /**
     * Map tagtype to severity level
     */
    private static function mapTagTypeToSeverity(?int $tagtype): string
    {
        return match ($tagtype) {
            1 => 'low',
            2 => 'medium',
            3 => 'high',
            4 => 'critical',
            default => 'medium',
        };
    }
}
