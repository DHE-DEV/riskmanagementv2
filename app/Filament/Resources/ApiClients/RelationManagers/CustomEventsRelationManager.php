<?php

namespace App\Filament\Resources\ApiClients\RelationManagers;

use App\Models\CustomEvent;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'customEvents';

    protected static ?string $title = 'Events';

    protected static ?string $modelLabel = 'Event';

    protected static ?string $pluralModelLabel = 'Events';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('priority')
                    ->label('Priorität')
                    ->formatStateUsing(fn (string $state): string => CustomEvent::getPriorityOptions()[$state] ?? $state),

                TextColumn::make('review_status')
                    ->label('Review')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending_review' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Freigegeben',
                        'pending_review' => 'Ausstehend',
                        'rejected' => 'Abgelehnt',
                        default => $state,
                    }),

                TextColumn::make('is_active')
                    ->label('Aktiv')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ja' : 'Nein')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('review_status')
                    ->label('Review-Status')
                    ->options([
                        'approved' => 'Freigegeben',
                        'pending_review' => 'Ausstehend',
                        'rejected' => 'Abgelehnt',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Freigeben')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CustomEvent $record): bool => $record->review_status === 'pending_review')
                    ->requiresConfirmation()
                    ->action(function (CustomEvent $record) {
                        $record->approve(auth()->id());
                        Notification::make()
                            ->title('Event freigegeben')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Ablehnen')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (CustomEvent $record): bool => $record->review_status === 'pending_review')
                    ->requiresConfirmation()
                    ->action(function (CustomEvent $record) {
                        $record->reject(auth()->id());
                        Notification::make()
                            ->title('Event abgelehnt')
                            ->warning()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
