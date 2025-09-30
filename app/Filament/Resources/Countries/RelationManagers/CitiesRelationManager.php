<?php

namespace App\Filament\Resources\Countries\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'cities';

    protected static ?string $title = 'Zugehörige Städte';

    protected static ?string $modelLabel = 'Stadt';

    protected static ?string $pluralModelLabel = 'Städte';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name_translations.de')
                    ->label('Name (Deutsch)')
                    ->required()
                    ->maxLength(255),

                TextInput::make('name_translations.en')
                    ->label('Name (Englisch)')
                    ->maxLength(255),

                Select::make('region_id')
                    ->label('Region')
                    ->options(function ($livewire) {
                        $country = $livewire->getOwnerRecord();
                        return \App\Models\Region::where('country_id', $country->id)
                            ->get()
                            ->mapWithKeys(fn ($region) => [$region->id => $region->getName('de') ?? $region->code])
                            ->toArray();
                    })
                    ->getSearchResultsUsing(function (string $search, $livewire) {
                        $country = $livewire->getOwnerRecord();
                        return \App\Models\Region::where('country_id', $country->id)
                            ->where(function ($query) use ($search) {
                                $query->where('code', 'like', "%{$search}%")
                                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))) LIKE LOWER(?)", ["%{$search}%"])
                                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en'))) LIKE LOWER(?)", ["%{$search}%"]);
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($region) => [$region->id => $region->getName('de') ?? $region->code])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_translations.de')
                            ->label('Name (Deutsch)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name_translations.en')
                            ->label('Name (Englisch)')
                            ->maxLength(255),
                    ])
                    ->createOptionUsing(function (array $data, $livewire) {
                        $country = $livewire->getOwnerRecord();
                        $data['country_id'] = $country->id;
                        return \App\Models\Region::create($data)->id;
                    }),

                Toggle::make('is_capital')
                    ->label('Hauptstadt'),

                TextInput::make('population')
                    ->label('Bevölkerung')
                    ->numeric(),

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
            ->recordTitleAttribute('name_translations')
            ->columns([
                Tables\Columns\TextColumn::make('city_name')
                    ->label('Stadt')
                    ->getStateUsing(fn ($record) => $record->getName('de'))
                    ->url(fn ($record): string => route('filament.admin.resources.cities.view', $record))
                    ->openUrlInNewTab()
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$search}%"])
                                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en')) LIKE ?", ["%{$search}%"]);
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) {$direction}");
                    }),
                Tables\Columns\TextColumn::make('region_name')
                    ->label('Region')
                    ->getStateUsing(fn ($record) => $record->region ? $record->region->getName('de') : 'Keine Region')
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->whereHas('region', function ($q) use ($search) {
                            $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$search}%"])
                              ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en')) LIKE ?", ["%{$search}%"]);
                        });
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->join('regions', 'cities.region_id', '=', 'regions.id', 'left')
                                    ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(regions.name_translations, '$.de')) {$direction}");
                    }),
                Tables\Columns\IconColumn::make('is_capital')
                    ->label('Hauptstadt')
                    ->boolean(),
                Tables\Columns\TextColumn::make('population')
                    ->label('Bevölkerung')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => $state ? number_format($state) : 'Unbekannt'),
                Tables\Columns\TextColumn::make('lat')
                    ->label('Breitengrad')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('lng')
                    ->label('Längengrad')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_capital')
                    ->label('Nur Hauptstädte')
                    ->query(fn (Builder $query): Builder => $query->where('is_capital', true))
                    ->toggle(),
                Tables\Filters\Filter::make('has_region')
                    ->label('Mit Region')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('region_id'))
                    ->toggle(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->recordTitleAttribute('city_name')
            ->recordTitle(fn ($record) => $record->getName('de'))
            ->bulkActions([
                // Keine Bulk Actions
            ])
            ->defaultSort('city_name', 'asc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}