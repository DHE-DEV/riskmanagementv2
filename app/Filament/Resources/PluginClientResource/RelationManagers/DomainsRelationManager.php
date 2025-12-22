<?php

namespace App\Filament\Resources\PluginClientResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    protected static ?string $title = 'Registrierte Domains';

    protected static ?string $icon = 'heroicon-o-globe-alt';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Domain kopiert!'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Hinzugefügt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine Domains')
            ->emptyStateDescription('Für diesen Kunden sind keine Domains registriert.');
    }
}
