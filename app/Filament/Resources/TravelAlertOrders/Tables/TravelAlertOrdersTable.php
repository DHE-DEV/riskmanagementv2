<?php

namespace App\Filament\Resources\TravelAlertOrders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TravelAlertOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Eingegangen')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('company')
                    ->label('Firma')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact_name')
                    ->label('Ansprechpartner')
                    ->getStateUsing(fn ($record) => trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ?: '-')
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Telefon')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('city')
                    ->label('Stadt')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('country')
                    ->label('Land')
                    ->sortable()
                    ->badge(),
                TextColumn::make('existing_billing')
                    ->label('Abrechnung')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'ja' ? 'Bestehend' : 'Neu')
                    ->color(fn (string $state) => $state === 'ja' ? 'success' : 'info'),
                TextColumn::make('trial_expires_at')
                    ->label('Test läuft ab')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('-')
                    ->color(fn ($record) => $record->trial_expires_at?->isPast() ? 'danger' : null),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
