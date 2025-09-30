<?php

namespace App\Filament\Resources\Cities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('city_name')
                    ->label('Stadtname')
                    ->getStateUsing(fn ($record) => $record->name_translations['de'] ?? $record->name_translations['en'] ?? 'Unbekannt')
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$search}%"])
                                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en')) LIKE ?", ["%{$search}%"]);
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) {$direction}");
                    }),
                TextColumn::make('country_name')
                    ->label('Land')
                    ->getStateUsing(fn ($record) => $record->country ? $record->country->getName('de') : 'Unbekannt')
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->whereHas('country', function ($q) use ($search) {
                            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$search}%"])
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en')) LIKE ?", ["%{$search}%"]);
                        });
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->join('countries', 'cities.country_id', '=', 'countries.id')
                                    ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(countries.name_translations, '$.de')) {$direction}");
                    }),
                TextColumn::make('region_name')
                    ->label('Region')
                    ->getStateUsing(fn ($record) => $record->region ? $record->region->getName('de') : 'Keine Region')
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->whereHas('region', function ($q) use ($search) {
                            $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))) LIKE LOWER(?)", ["%{$search}%"])
                              ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en'))) LIKE LOWER(?)", ["%{$search}%"])
                              ->orWhere('code', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->join('regions', 'cities.region_id', '=', 'regions.id', 'left')
                                    ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(regions.name_translations, '$.de')) {$direction}");
                    }),
                TextColumn::make('population')
                    ->label('Bevölkerung')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_capital')
                    ->label('Hauptstadt')
                    ->boolean(),
                TextColumn::make('lat')
                    ->label('Breitengrad')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lng')
                    ->label('Längengrad')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('city_name', 'asc')
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
