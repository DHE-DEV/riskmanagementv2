<?php

namespace App\Filament\Resources\Airlines\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AirlinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('iata_code')
                    ->label('IATA')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('icao_code')
                    ->label('ICAO')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('homeCountry.name_translations')
                    ->label('Heimatland')
                    ->getStateUsing(fn ($record) => $record->homeCountry?->name_translations['de'] ?? $record->homeCountry?->name_translations['en'] ?? '-')
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('homeCountry', function ($q) use ($search) {
                            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$search}%"])
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en')) LIKE ?", ["%{$search}%"]);
                        });
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $query->leftJoin('countries', 'airlines.home_country_id', '=', 'countries.id')
                              ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(countries.name_translations, '$.de')) {$direction}");
                    })
                    ->toggleable(),

                TextColumn::make('headquarters')
                    ->label('Hauptsitz')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cabin_classes')
                    ->label('Kabinenklassen')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record->cabin_classes) {
                            return [];
                        }
                        $labels = [
                            'economy' => 'Economy',
                            'premium_economy' => 'Premium',
                            'business' => 'Business',
                            'first' => 'First',
                        ];
                        return collect($record->cabin_classes)->map(fn($class) => $labels[$class] ?? $class)->toArray();
                    })
                    ->toggleable(),

                TextColumn::make('airports_count')
                    ->label('Direktverbindungen')
                    ->counts('airports')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Aktualisiert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
