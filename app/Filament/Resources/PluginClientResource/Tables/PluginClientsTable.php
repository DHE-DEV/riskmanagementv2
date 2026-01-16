<?php

namespace App\Filament\Resources\PluginClientResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PluginClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('contact_name')
                    ->label('Ansprechpartner')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('E-Mail kopiert!'),

                TextColumn::make('customer.company_city')
                    ->label('Ort')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('activeKey.public_key')
                    ->label('API-Key')
                    ->copyable()
                    ->copyMessage('API-Key kopiert!')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->activeKey?->public_key),

                TextColumn::make('domains_count')
                    ->label('Domains')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('usage_events_count')
                    ->label('Aufrufe')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktiv',
                        'inactive' => 'Inaktiv',
                        'suspended' => 'Gesperrt',
                        default => $state,
                    }),

                IconColumn::make('allow_app_access')
                    ->label('App')
                    ->boolean()
                    ->trueIcon('heroicon-o-device-phone-mobile')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Registriert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktiv',
                        'inactive' => 'Inaktiv',
                        'suspended' => 'Gesperrt',
                    ]),

                Filter::make('has_usage')
                    ->label('Mit Aufrufen')
                    ->query(fn (Builder $query): Builder => $query->has('usageEvents'))
                    ->toggle(),

                Filter::make('created_this_month')
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
                BulkActionGroup::make([
                    DeleteBulkAction::make()
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
