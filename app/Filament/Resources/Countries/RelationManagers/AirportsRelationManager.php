<?php

namespace App\Filament\Resources\Countries\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                    ->label('Flughafenname')
                    ->required()
                    ->maxLength(255),

                TextInput::make('iata_code')
                    ->label('IATA Code')
                    ->maxLength(3)
                    ->helperText('3-stelliger IATA Code (z.B. MUC für München)'),

                TextInput::make('icao_code')
                    ->label('ICAO Code')
                    ->maxLength(4)
                    ->helperText('4-stelliger ICAO Code (z.B. EDDM für München)'),

                Select::make('city_id')
                    ->label('Stadt')
                    ->options(function ($livewire) {
                        $country = $livewire->getOwnerRecord();
                        return \App\Models\City::where('country_id', $country->id)
                            ->get()
                            ->mapWithKeys(fn ($city) => [$city->id => $city->getName('de') ?? $city->getName('en')])
                            ->toArray();
                    })
                    ->getSearchResultsUsing(function (string $search, $livewire) {
                        $country = $livewire->getOwnerRecord();
                        return \App\Models\City::where('country_id', $country->id)
                            ->where(function ($query) use ($search) {
                                $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))) LIKE LOWER(?)", ["%{$search}%"])
                                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en'))) LIKE LOWER(?)", ["%{$search}%"]);
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($city) => [$city->id => $city->getName('de') ?? $city->getName('en')])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),

                Select::make('type')
                    ->label('Flughafentyp')
                    ->options([
                        'international' => 'Internationaler Flughafen',
                        'large_airport' => 'Großer Flughafen',
                        'medium_airport' => 'Mittlerer Flughafen',
                        'small_airport' => 'Kleiner Flughafen',
                        'heliport' => 'Hubschrauberlandeplatz',
                        'seaplane_base' => 'Wasserflugzeugbasis',
                    ])
                    ->default('medium_airport'),

                TextInput::make('altitude')
                    ->label('Höhe (Meter)')
                    ->numeric()
                    ->helperText('Höhe über dem Meeresspiegel in Metern'),

                TextInput::make('timezone')
                    ->label('Zeitzone')
                    ->maxLength(255)
                    ->helperText('z.B. Europe/Berlin'),

                Textarea::make('description')
                    ->label('Beschreibung')
                    ->maxLength(1000)
                    ->rows(3),

                TextInput::make('coordinates_import')
                    ->label('Google Maps Koordinaten')
                    ->placeholder('z.B. 48.1351, 11.5820 oder 48.1351,11.5820')
                    ->helperText('Koordinaten aus Google Maps einfügen (Format: Lat, Lng)')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (empty($state)) {
                            return;
                        }

                        $state = trim($state);
                        $state = str_replace([' ', "\t", "\n"], '', $state);

                        if (str_contains($state, ',')) {
                            $parts = explode(',', $state);
                            if (count($parts) >= 2) {
                                $lat = trim($parts[0]);
                                $lng = trim($parts[1]);

                                if (is_numeric($lat) && is_numeric($lng)) {
                                    $lat = floatval($lat);
                                    $lng = floatval($lng);

                                    if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                                        $set('lat', $lat);
                                        $set('lng', $lng);
                                    }
                                }
                            }
                        }
                    })
                    ->dehydrated(false),

                TextInput::make('lat')
                    ->label('Breitengrad')
                    ->numeric()
                    ->step(0.000001)
                    ->minValue(-90)
                    ->maxValue(90),

                TextInput::make('lng')
                    ->label('Längengrad')
                    ->numeric()
                    ->step(0.000001)
                    ->minValue(-180)
                    ->maxValue(180),
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
                            $q->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))) LIKE LOWER(?)", ["%{$search}%"])
                              ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en'))) LIKE LOWER(?)", ["%{$search}%"]);
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
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
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