<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AccessibleAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'accessibleAccounts';

    protected static ?string $title = 'Zugriff auf andere Accounts';

    protected static ?string $modelLabel = 'Account-Zugriff';

    protected static ?string $pluralModelLabel = 'Account-Zugriffe';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('app_code')
                    ->label('Code')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Firma')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company_city')
                    ->label('Stadt'),
                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Berechtigt seit')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Account-Zugriff hinzufügen')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function () {
                        return Customer::where('id', '!=', $this->getOwnerRecord()->id);
                    })
                    ->recordTitle(fn (Customer $record) => "{$record->company_name} ({$record->email})")
                    ->recordSelectSearchColumns(['company_name', 'email', 'app_code']),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Entfernen'),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make()
                    ->label('Ausgewählte entfernen'),
            ]);
    }
}
