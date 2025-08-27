<?php

namespace App\Filament\Resources\Countries\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AirportsRelationManager extends RelationManager
{
    protected static string $relationship = 'airports';

    protected static ?string $title = 'Zugehörige Flughäfen';

    protected static ?string $modelLabel = 'Flughafen';

    protected static ?string $pluralModelLabel = 'Flughäfen';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Flughafenname')
                    ->url(fn ($record): string => route('filament.admin.resources.airports.view', $record))
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('iata_code')
                    ->label('IATA Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('icao_code')
                    ->label('ICAO Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('secondary'),
                Tables\Columns\TextColumn::make('city_name')
                    ->label('Stadt')
                    ->getStateUsing(fn ($record) => $record->city ? $record->city->getName('de') : 'Keine Stadt')
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->whereHas('city', function ($q) use ($search) {
                            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$search}%"])
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en')) LIKE ?", ["%{$search}%"]);
                        });
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->join('cities', 'airports.city_id', '=', 'cities.id', 'left')
                                    ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(cities.name_translations, '$.de')) {$direction}");
                    }),
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'international' => 'Internationaler Flughafen',
                        'large_airport' => 'Großer Flughafen',
                        'medium_airport' => 'Mittlerer Flughafen',
                        'small_airport' => 'Kleiner Flughafen',
                        'heliport' => 'Hubschrauberlandeplatz',
                        'seaplane_base' => 'Wasserflugzeugbasis',
                        default => $state
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'international' => 'danger',
                        'large_airport' => 'success',
                        'medium_airport' => 'warning',
                        'small_airport' => 'gray',
                        'heliport' => 'info',
                        'seaplane_base' => 'purple',
                        default => 'gray'
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('altitude')
                    ->label('Höhe (m)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('timezone')
                    ->label('Zeitzone')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('coordinates')
                    ->label('Koordinaten')
                    ->getStateUsing(fn ($record) => $record->lat && $record->lng ? "{$record->lat}, {$record->lng}" : 'Nicht verfügbar')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Flughafentyp')
                    ->options([
                        'international' => 'Internationaler Flughafen',
                        'large_airport' => 'Großer Flughafen',
                        'medium_airport' => 'Mittlerer Flughafen',
                        'small_airport' => 'Kleiner Flughafen',
                        'heliport' => 'Hubschrauberlandeplatz',
                        'seaplane_base' => 'Wasserflugzeugbasis',
                    ])
                    ->multiple(),
                Tables\Filters\Filter::make('has_iata')
                    ->label('Mit IATA Code')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('iata_code'))
                    ->toggle(),
                Tables\Filters\Filter::make('has_icao')
                    ->label('Mit ICAO Code')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('icao_code'))
                    ->toggle(),
            ])
            ->headerActions([
                // Keine Create Actions, da Flughäfen separat verwaltet werden
            ])
            ->actions([
                // Actions entfernt - Navigation über Spalten-URL wenn nötig
            ])
            ->recordTitleAttribute('name')
            ->recordTitle(fn ($record) => $record->name)
            ->bulkActions([
                // Keine Bulk Actions
            ])
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}