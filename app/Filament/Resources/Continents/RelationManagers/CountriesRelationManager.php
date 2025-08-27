<?php

namespace App\Filament\Resources\Continents\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CountriesRelationManager extends RelationManager
{
    protected static string $relationship = 'countries';

    protected static ?string $title = 'Zugehörige Länder';

    protected static ?string $modelLabel = 'Land';

    protected static ?string $pluralModelLabel = 'Länder';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name_translations')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name_translations')
            ->columns([
                Tables\Columns\TextColumn::make('german_name')
                    ->label('Land')
                    ->getStateUsing(fn ($record) => $record->getName('de'))
                    ->url(fn ($record): string => route('filament.admin.resources.countries.view', $record))
                    ->openUrlInNewTab()
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$search}%"]);
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) {$direction}");
                    }),
                Tables\Columns\TextColumn::make('iso_code')
                    ->label('ISO Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('iso3_code')
                    ->label('ISO3 Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_eu_member')
                    ->label('EU')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_schengen_member')
                    ->label('Schengen')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency_code')
                    ->label('Währung')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('population')
                    ->label('Bevölkerung')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => number_format($state)),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_eu_member')
                    ->label('EU-Mitglieder')
                    ->query(fn (Builder $query): Builder => $query->where('is_eu_member', true))
                    ->toggle(),
                Tables\Filters\Filter::make('is_schengen_member')
                    ->label('Schengen-Mitglieder')
                    ->query(fn (Builder $query): Builder => $query->where('is_schengen_member', true))
                    ->toggle(),
            ])
            ->headerActions([
                // Keine Create Actions, da Länder separat verwaltet werden
            ])
            ->actions([
                // Actions entfernt - Navigation über recordUrl
            ])
            ->recordTitleAttribute('german_name')
            ->recordTitle(fn ($record) => $record->getName('de'))
            ->bulkActions([
                // Keine Bulk Actions
            ])
            ->defaultSort('german_name', 'asc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25);
    }
}