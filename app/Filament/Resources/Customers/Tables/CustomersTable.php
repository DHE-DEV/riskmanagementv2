<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('customer_type')
                    ->label('Kundentyp')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'private' => 'Privat',
                        'business' => 'Geschäftlich',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'private' => 'info',
                        'business' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('email_verified_at')
                    ->label('E-Mail verifiziert')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !is_null($record->email_verified_at))
                    ->sortable(),

                IconColumn::make('directory_listing_active')
                    ->label('Adressverzeichnis')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('branch_management_active')
                    ->label('Filialen aktiv')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('branches_count')
                    ->label('Filialen')
                    ->counts('branches')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('passolution_subscription_type')
                    ->label('Passolution Abo')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('provider')
                    ->label('Login via')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'E-Mail')
                    ->color(fn (?string $state): string => $state ? 'warning' : 'gray')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Registriert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('deleted_at')
                    ->label('Gelöscht am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('customer_type')
                    ->label('Kundentyp')
                    ->options([
                        'private' => 'Privat',
                        'business' => 'Geschäftlich',
                    ]),

                SelectFilter::make('email_verified')
                    ->label('E-Mail Status')
                    ->options([
                        'verified' => 'Verifiziert',
                        'unverified' => 'Nicht verifiziert',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value'] === 'verified') {
                            return $query->whereNotNull('email_verified_at');
                        }
                        if ($state['value'] === 'unverified') {
                            return $query->whereNull('email_verified_at');
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Löschen')
                    ->modalHeading('Customer löschen')
                    ->modalDescription('Möchten Sie diesen Customer wirklich löschen? Dies ist ein Soft Delete.')
                    ->successNotificationTitle('Customer gelöscht'),
                ForceDeleteAction::make()
                    ->label('Endgültig löschen')
                    ->modalHeading('Customer endgültig löschen')
                    ->modalDescription('ACHTUNG: Dies löscht den Customer permanent aus der Datenbank. Der Benutzer kann sich danach erneut registrieren. Diese Aktion kann nicht rückgängig gemacht werden!')
                    ->modalSubmitActionLabel('Endgültig löschen')
                    ->successNotificationTitle('Customer wurde endgültig gelöscht')
                    ->color('danger'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Ausgewählte löschen'),
                    ForceDeleteBulkAction::make()
                        ->label('Ausgewählte endgültig löschen')
                        ->modalHeading('Customers endgültig löschen')
                        ->modalDescription('ACHTUNG: Dies löscht die ausgewählten Customers permanent. Sie können sich danach erneut registrieren.')
                        ->modalSubmitActionLabel('Endgültig löschen'),
                    RestoreBulkAction::make()
                        ->label('Ausgewählte wiederherstellen'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Customers')
            ->emptyStateDescription('Es wurden noch keine Customers registriert.');
    }
}
