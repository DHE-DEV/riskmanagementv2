<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class GtmApiRequestLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'gtmApiRequestLogs';

    protected static ?string $title = 'GTM API Logs';

    protected static ?string $modelLabel = 'API Request';

    protected static ?string $pluralModelLabel = 'API Requests';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Zeitpunkt')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('method')
                    ->label('Methode')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('endpoint')
                    ->label('Endpunkt')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('response_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state < 300 => 'success',
                        $state < 400 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('response_time_ms')
                    ->label('Antwortzeit')
                    ->suffix(' ms')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP-Adresse')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('response_status')
                    ->label('Status')
                    ->options([
                        '200' => '200 OK',
                        '401' => '401 Unauthorized',
                        '403' => '403 Forbidden',
                        '404' => '404 Not Found',
                        '429' => '429 Too Many Requests',
                        '500' => '500 Server Error',
                    ]),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Keine API Requests')
            ->emptyStateDescription('Es wurden noch keine GTM API Anfragen protokolliert.')
            ->emptyStateIcon('heroicon-o-document-magnifying-glass');
    }
}
