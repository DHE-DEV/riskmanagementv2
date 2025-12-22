<?php

namespace App\Filament\Resources\PluginClientResource\RelationManagers;

use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsageEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'usageEvents';

    protected static ?string $title = 'Aufrufe';

    protected static string|BackedEnum|null $icon = 'heroicon-o-chart-bar';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeitpunkt')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('domain')
                    ->label('Domain')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('path')
                    ->label('Pfad')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->path)
                    ->placeholder('-'),

                Tables\Columns\BadgeColumn::make('event_type')
                    ->label('Event-Typ')
                    ->colors([
                        'primary' => 'page_load',
                        'success' => 'click',
                        'info' => fn ($state) => $state !== 'page_load' && $state !== 'click',
                    ]),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('Browser')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->user_agent)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->label('Event-Typ')
                    ->options([
                        'page_load' => 'Page Load',
                        'click' => 'Click',
                    ]),

                Tables\Filters\SelectFilter::make('domain')
                    ->label('Domain')
                    ->options(function (RelationManager $livewire) {
                        return $livewire->getOwnerRecord()
                            ->usageEvents()
                            ->distinct()
                            ->pluck('domain', 'domain')
                            ->toArray();
                    }),

                Tables\Filters\Filter::make('today')
                    ->label('Heute')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()))
                    ->toggle(),

                Tables\Filters\Filter::make('this_week')
                    ->label('Diese Woche')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->startOfWeek()))
                    ->toggle(),

                Tables\Filters\Filter::make('this_month')
                    ->label('Dieser Monat')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('created_at', now()->month))
                    ->toggle(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Aufrufe')
            ->emptyStateDescription('Es wurden noch keine Widget-Aufrufe für diesen Kunden registriert.')
            ->poll('30s');
    }
}
