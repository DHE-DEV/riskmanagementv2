<?php

namespace App\Filament\Resources\ApiClients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ApiClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktiv',
                        'inactive' => 'Inaktiv',
                        'suspended' => 'Gesperrt',
                        default => $state,
                    }),

                IconColumn::make('auto_approve_events')
                    ->label('Auto-Freigabe')
                    ->boolean(),

                TextColumn::make('custom_events_count')
                    ->label('Events')
                    ->counts('customEvents')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Erstellt')
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
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
