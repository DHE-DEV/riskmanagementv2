<?php

namespace App\Filament\Resources\Countries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Continent;

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Globale Suche für JSON-Felder
                return $query;
            })
            ->columns([
                TextColumn::make('german_name')
                    ->label('Name')
                    ->getStateUsing(fn ($record) => $record->getName('de'))
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->where(function ($q) use ($search) {
                            $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))) LIKE LOWER(?)", ["%{$search}%"])
                              ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en'))) LIKE LOWER(?)", ["%{$search}%"])
                              ->orWhere('iso_code', 'like', "%{$search}%")
                              ->orWhere('iso3_code', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) {$direction}");
                    }),
                TextColumn::make('iso_code')
                    ->label('ISO Code')
                    ->sortable(),
                TextColumn::make('iso3_code')
                    ->label('ISO3 Code')
                    ->sortable(),
                IconColumn::make('is_eu_member')
                    ->label('EU')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_schengen_member')
                    ->label('Schengen')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('continent_name')
                    ->label('Kontinent')
                    ->getStateUsing(fn ($record) => $record->continent ? ($record->continent->name_translations['de'] ?? $record->continent->name_translations['en'] ?? $record->continent->code) : 'Unbekannt')
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->whereHas('continent', function ($q) use ($search) {
                            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$search}%"])
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en')) LIKE ?", ["%{$search}%"])
                              ->orWhere('code', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->join('continents', 'countries.continent_id', '=', 'continents.id')
                                    ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(continents.name_translations, '$.de')) {$direction}");
                    }),
                TextColumn::make('currency_code')
                    ->label('Währungscode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('currency_name')
                    ->label('Währungsname')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('currency_symbol')
                    ->searchable(),
                TextColumn::make('phone_prefix')
                    ->searchable(),
                TextColumn::make('timezone')
                    ->label('Zeitzone')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('population')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('area_km2')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lat')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lng')
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
            ->defaultSort('german_name', 'asc')
            ->filters([
                Filter::make('is_eu_member')
                    ->label('EU-Mitglieder')
                    ->query(fn (Builder $query): Builder => $query->where('is_eu_member', true))
                    ->toggle(),
                    
                Filter::make('is_schengen_member')
                    ->label('Schengen-Mitglieder')
                    ->query(fn (Builder $query): Builder => $query->where('is_schengen_member', true))
                    ->toggle(),
                    
                SelectFilter::make('continent_id')
                    ->label('Kontinent')
                    ->relationship('continent', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name_translations['de'] ?? $record->name_translations['en'] ?? $record->code)
                    ->searchable()
                    ->preload(),
                    
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
