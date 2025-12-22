<?php

namespace App\Filament\Resources\PluginClientResource\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class PluginClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Ansprechpartner')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('E-Mail kopiert!'),

                Tables\Columns\TextColumn::make('customer.company_city')
                    ->label('Ort')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('activeKey.public_key')
                    ->label('API-Key')
                    ->copyable()
                    ->copyMessage('API-Key kopiert!')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->activeKey?->public_key),

                Tables\Columns\TextColumn::make('domains_count')
                    ->label('Domains')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('usage_events_count')
                    ->label('Aufrufe')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger' => 'suspended',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktiv',
                        'inactive' => 'Inaktiv',
                        'suspended' => 'Gesperrt',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registriert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktiv',
                        'inactive' => 'Inaktiv',
                        'suspended' => 'Gesperrt',
                    ]),

                Tables\Filters\Filter::make('has_usage')
                    ->label('Mit Aufrufen')
                    ->query(fn (Builder $query): Builder => $query->has('usageEvents'))
                    ->toggle(),

                Tables\Filters\Filter::make('created_this_month')
                    ->label('Diesen Monat registriert')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('created_at', now()->month))
                    ->toggle(),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Details')
                    ->icon('heroicon-o-eye'),

                Action::make('toggle_status')
                    ->label(fn ($record) => $record->status === 'active' ? 'Deaktivieren' : 'Aktivieren')
                    ->icon(fn ($record) => $record->status === 'active' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->status === 'active' ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => $record->status === 'active' ? 'inactive' : 'active',
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen')
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-puzzle-piece')
            ->emptyStateHeading('Keine Plugin-Kunden')
            ->emptyStateDescription('Es haben sich noch keine Kunden für das Plugin registriert.');
    }
}
