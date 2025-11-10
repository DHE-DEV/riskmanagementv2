<?php

namespace App\Filament\Resources\CustomEvents\RelationManagers;

use App\Models\Country;
use App\Models\Region;
use App\Models\City;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Components\Utilities\Get as SchemaGet;
use Filament\Schemas\Components\Utilities\Set as SchemaSet;

class CountriesRelationManager extends RelationManager
{
    protected static string $relationship = 'countries';

    protected static ?string $title = 'Länder & Standorte';

    protected static ?string $modelLabel = 'Land/Standort';

    protected static ?string $pluralModelLabel = 'Länder/Standorte';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormSection::make('Land auswählen')
                    ->schema([
                        Select::make('recordId')
                            ->label('Land')
                            ->options(fn () => Country::query()
                                ->orderBy('name_translations->de')
                                ->get()
                                ->mapWithKeys(fn (Country $c) => [$c->id => $c->getName('de') . ' (' . $c->iso_code . ')'])
                                ->toArray()
                            )
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Country::query()
                                ->where('name_translations->de', 'like', '%' . $search . '%')
                                ->orWhere('name_translations->en', 'like', '%' . $search . '%')
                                ->orWhere('iso_code', 'like', '%' . $search . '%')
                                ->orderBy('name_translations->de')
                                ->get()
                                ->mapWithKeys(fn (Country $c) => [$c->id => $c->getName('de') . ' (' . $c->iso_code . ')'])
                                ->toArray()
                            )
                            ->required()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (SchemaSet $set, ?string $state) {
                                if ($state) {
                                    $country = Country::find($state);
                                    if ($country && $country->lat && $country->lng) {
                                        $set('latitude', $country->lat);
                                        $set('longitude', $country->lng);
                                    }
                                }
                            }),
                    ]),

                FormSection::make('Standort-Koordinaten')
                    ->description('Geben Sie spezifische Koordinaten für diesen Standort an oder verwenden Sie die Standard-Koordinaten des Landes.')
                    ->schema([
                        Toggle::make('use_default_coordinates')
                            ->label('Standard-Koordinaten der Hauptstadt des Landes verwenden')
                            ->default(true)
                            ->reactive()
                            ->afterStateUpdated(function (SchemaGet $get, SchemaSet $set, ?bool $state) {
                                if ($state && $get('recordId')) {
                                    $country = Country::find($get('recordId'));
                                    if ($country && $country->lat && $country->lng) {
                                        $set('latitude', $country->lat);
                                        $set('longitude', $country->lng);
                                    }
                                }
                            }),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('latitude')
                                    ->label('Breitengrad')
                                    ->numeric()
                                    ->minValue(-90)
                                    ->maxValue(90)
                                    ->step('any')
                                    ->disabled(fn (SchemaGet $get): bool => (bool) $get('use_default_coordinates'))
                                    ->required(fn (SchemaGet $get): bool => !(bool) $get('use_default_coordinates'))
                                    ->placeholder('50.1109'),

                                TextInput::make('longitude')
                                    ->label('Längengrad')
                                    ->numeric()
                                    ->minValue(-180)
                                    ->maxValue(180)
                                    ->step('any')
                                    ->disabled(fn (SchemaGet $get): bool => (bool) $get('use_default_coordinates'))
                                    ->required(fn (SchemaGet $get): bool => !(bool) $get('use_default_coordinates'))
                                    ->placeholder('8.6821'),
                            ]),

                        Textarea::make('location_note')
                            ->label('Standort-Notiz')
                            ->rows(2)
                            ->placeholder('z.B. Hauptstadt, Flughafen Frankfurt, etc.')
                            ->helperText('Optional: Beschreiben Sie den genauen Standort'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('iso_code')
                    ->label('ISO')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Land')
                    ->getStateUsing(fn (Country $record): string => $record->getName('de'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('name_translations->de', 'like', "%{$search}%")
                            ->orWhere('name_translations->en', 'like', "%{$search}%");
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('location_details')
                    ->label('Region / Stadt')
                    ->getStateUsing(function ($record) {
                        if (!$record || !$record->pivot) {
                            return '-';
                        }

                        $parts = [];

                        if ($record->pivot->region_id) {
                            $region = Region::find($record->pivot->region_id);
                            if ($region) {
                                $parts[] = $region->getName('de');
                            }
                        }

                        if ($record->pivot->city_id) {
                            $city = City::find($record->pivot->city_id);
                            if ($city) {
                                $parts[] = $city->getName('de');
                            }
                        }

                        return !empty($parts) ? implode(' / ', $parts) : '-';
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('coordinates')
                    ->label('Koordinaten')
                    ->getStateUsing(function ($record) {
                        if (!$record || !$record->pivot) {
                            return '-';
                        }

                        // Priorität: Stadt > Region > Hauptstadt > Land
                        $lat = null;
                        $lng = null;

                        if ($record->pivot->use_default_coordinates) {
                            // 1. Prüfe Stadt-Koordinaten
                            if ($record->pivot->city_id) {
                                $city = City::find($record->pivot->city_id);
                                if ($city && $city->lat && $city->lng) {
                                    $lat = $city->lat;
                                    $lng = $city->lng;
                                }
                            }

                            // 2. Prüfe Region-Koordinaten (wenn keine Stadt-Koordinaten)
                            if (!$lat && !$lng && $record->pivot->region_id) {
                                $region = Region::find($record->pivot->region_id);
                                if ($region && $region->lat && $region->lng) {
                                    $lat = $region->lat;
                                    $lng = $region->lng;
                                }
                            }

                            // 3. Prüfe Hauptstadt-Koordinaten (wenn keine Stadt/Region-Koordinaten)
                            if (!$lat && !$lng && $record->capital && $record->capital->lat && $record->capital->lng) {
                                $lat = $record->capital->lat;
                                $lng = $record->capital->lng;
                            }

                            // 4. Fallback: geografisches Zentrum des Landes
                            if (!$lat && !$lng) {
                                $lat = $record->lat;
                                $lng = $record->lng;
                            }
                        } else {
                            // Verwende individuelle Koordinaten aus dem Pivot
                            $lat = $record->pivot->latitude;
                            $lng = $record->pivot->longitude;
                        }

                        if (!$lat || !$lng) {
                            return '-';
                        }

                        $coords = "{$lat}, {$lng}";

                        // Füge Standort-Notiz hinzu falls vorhanden
                        if ($record->pivot->location_note) {
                            $coords .= " ({$record->pivot->location_note})";
                        }

                        return $coords;
                    })
                    ->html()
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state || $state === '-') {
                            return $state;
                        }

                        // Priorität: Stadt > Region > Hauptstadt > Land
                        $lat = null;
                        $lng = null;

                        if ($record->pivot->use_default_coordinates) {
                            // 1. Prüfe Stadt-Koordinaten
                            if ($record->pivot->city_id) {
                                $city = City::find($record->pivot->city_id);
                                if ($city && $city->lat && $city->lng) {
                                    $lat = $city->lat;
                                    $lng = $city->lng;
                                }
                            }

                            // 2. Prüfe Region-Koordinaten (wenn keine Stadt-Koordinaten)
                            if (!$lat && !$lng && $record->pivot->region_id) {
                                $region = Region::find($record->pivot->region_id);
                                if ($region && $region->lat && $region->lng) {
                                    $lat = $region->lat;
                                    $lng = $region->lng;
                                }
                            }

                            // 3. Prüfe Hauptstadt-Koordinaten (wenn keine Stadt/Region-Koordinaten)
                            if (!$lat && !$lng && $record->capital && $record->capital->lat && $record->capital->lng) {
                                $lat = $record->capital->lat;
                                $lng = $record->capital->lng;
                            }

                            // 4. Fallback: geografisches Zentrum des Landes
                            if (!$lat && !$lng) {
                                $lat = $record->lat;
                                $lng = $record->lng;
                            }
                        } else {
                            // Verwende individuelle Koordinaten aus dem Pivot
                            $lat = $record->pivot->latitude;
                            $lng = $record->pivot->longitude;
                        }

                        if (!$lat || !$lng) {
                            return '-';
                        }

                        // Dashboard-URL mit Koordinaten und Event-ID für Zoom
                        $eventId = $this->getOwnerRecord()->id;
                        $dashboardUrl = "/dashboard?lat={$lat}&lng={$lng}&zoom=12&event={$eventId}";

                        // Anzeige-Text mit optionaler Standort-Notiz
                        $displayText = "{$lat}, {$lng}";
                        if ($record->pivot->location_note) {
                            $displayText .= " ({$record->pivot->location_note})";
                        }

                        // Link zur Dashboard-Karte mit Icon
                        return '<a href="' . $dashboardUrl . '" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>' . $displayText . '</span>
                        </a>';
                    })
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_eu_member')
                    ->label('EU')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_schengen_member')
                    ->label('Schengen')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('continent.name_translations')
                    ->label('Kontinent')
                    ->formatStateUsing(fn ($record) => $record->continent ? $record->continent->getName('de') : '-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Hinzugefügt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('continent_id')
                    ->label('Kontinent')
                    ->relationship('continent', 'name_translations', fn ($query) => $query->orderBy('name_translations->de'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getName('de'))
                    ->multiple()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_eu_member')
                    ->label('EU-Mitglied')
                    ->placeholder('Alle')
                    ->trueLabel('Ja')
                    ->falseLabel('Nein'),

                Tables\Filters\TernaryFilter::make('is_schengen_member')
                    ->label('Schengen-Mitglied')
                    ->placeholder('Alle')
                    ->trueLabel('Ja')
                    ->falseLabel('Nein'),

                Tables\Filters\TernaryFilter::make('has_custom_coordinates')
                    ->label('Eigene Koordinaten')
                    ->placeholder('Alle')
                    ->trueLabel('Eigene')
                    ->falseLabel('Standard')
                    ->query(function (Builder $query, $data) {
                        $value = $data['value'] ?? null;
                        return match ($value) {
                            true => $query->wherePivot('use_default_coordinates', false),
                            false => $query->wherePivot('use_default_coordinates', true),
                            default => $query,
                        };
                    }),
            ])
            ->headerActions([
                \Filament\Actions\AttachAction::make()
                    ->label('Land hinzufügen')
                    ->modalHeading('Land/Standort hinzufügen')
                    ->modalSubmitActionLabel('Hinzufügen')
                    ->modalCancelActionLabel('Abbrechen')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $ownerRecord = $this->getOwnerRecord();
                        $existingIds = $ownerRecord->countries()->pluck('countries.id')->toArray();

                        return $query->whereNotIn('countries.id', $existingIds)
                            ->orderBy('name_translations->de')
                            ->limit(50);
                    })
                    ->form(fn (\Filament\Actions\AttachAction $action): array => [
                        Select::make('recordId')
                            ->label('Land auswählen')
                            ->options(function () {
                                $ownerRecord = $this->getOwnerRecord();
                                $existingIds = $ownerRecord->countries()->pluck('countries.id')->toArray();

                                return Country::query()
                                    ->whereNotIn('id', $existingIds)
                                    ->orderBy('name_translations->de')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Country $c) => [$c->id => $c->getName('de') . ' (' . $c->iso_code . ')'])
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($set) {
                                // Reset region and city when country changes
                                $set('region_id', null);
                                $set('city_id', null);
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                $ownerRecord = $this->getOwnerRecord();
                                $existingIds = $ownerRecord->countries()->pluck('countries.id')->toArray();

                                $searchLower = mb_strtolower($search);

                                return Country::query()
                                    ->whereNotIn('id', $existingIds)
                                    ->where(function ($query) use ($searchLower) {
                                        $query->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.de"))) LIKE ?', ['%' . $searchLower . '%'])
                                              ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.en"))) LIKE ?', ['%' . $searchLower . '%'])
                                              ->orWhereRaw('LOWER(iso_code) LIKE ?', ['%' . $searchLower . '%'])
                                              ->orWhereRaw('LOWER(iso3_code) LIKE ?', ['%' . $searchLower . '%']);
                                    })
                                    ->orderBy('name_translations->de')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(fn (Country $c) => [$c->id => $c->getName('de') . ' (' . $c->iso_code . ')'])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string =>
                                $value ? Country::find($value)?->getName('de') . ' (' . Country::find($value)?->iso_code . ')' : null
                            ),

                        Select::make('region_id')
                            ->label('Region (optional)')
                            ->options(function ($get) {
                                $countryId = $get('recordId');
                                if (!$countryId) {
                                    return [];
                                }

                                return Region::query()
                                    ->where('country_id', $countryId)
                                    ->orderBy('name_translations->de')
                                    ->get()
                                    ->mapWithKeys(fn (Region $r) => [$r->id => $r->getName('de')])
                                    ->toArray();
                            })
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($get, $set, ?string $state) {
                                // Reset city when region changes
                                $set('city_id', null);

                                // Update coordinates if using default coordinates
                                if ($state && $get('use_default_coordinates')) {
                                    $region = Region::find($state);
                                    if ($region && $region->lat && $region->lng) {
                                        $set('latitude', $region->lat);
                                        $set('longitude', $region->lng);
                                    }
                                }
                            })
                            ->getSearchResultsUsing(function (string $search, $get) {
                                $countryId = $get('recordId');
                                if (!$countryId) {
                                    return [];
                                }

                                $searchLower = mb_strtolower($search);

                                return Region::query()
                                    ->where('country_id', $countryId)
                                    ->where(function ($query) use ($searchLower) {
                                        $query->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.de"))) LIKE ?', ['%' . $searchLower . '%'])
                                              ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.en"))) LIKE ?', ['%' . $searchLower . '%'])
                                              ->orWhereRaw('LOWER(code) LIKE ?', ['%' . $searchLower . '%']);
                                    })
                                    ->orderBy('name_translations->de')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(fn (Region $r) => [$r->id => $r->getName('de')])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string =>
                                $value ? Region::find($value)?->getName('de') : null
                            )
                            ->visible(fn ($get) => $get('recordId') !== null)
                            ->helperText('Optional: Wählen Sie eine Region des ausgewählten Landes'),

                        Select::make('city_id')
                            ->label('Stadt (optional)')
                            ->options(function ($get) {
                                $countryId = $get('recordId');
                                $regionId = $get('region_id');

                                if (!$countryId) {
                                    return [];
                                }

                                $query = City::query()->where('country_id', $countryId);

                                // If region is selected, filter cities by region
                                if ($regionId) {
                                    $query->where('region_id', $regionId);
                                }

                                return $query
                                    ->orderBy('name_translations->de')
                                    ->get()
                                    ->mapWithKeys(fn (City $c) => [$c->id => $c->getName('de')])
                                    ->toArray();
                            })
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($get, $set, ?string $state) {
                                if ($state) {
                                    $city = City::find($state);
                                    if ($city && $city->lat && $city->lng && !$get('use_default_coordinates')) {
                                        $set('latitude', $city->lat);
                                        $set('longitude', $city->lng);
                                    }
                                }
                            })
                            ->getSearchResultsUsing(function (string $search, $get) {
                                $countryId = $get('recordId');
                                $regionId = $get('region_id');

                                if (!$countryId) {
                                    return [];
                                }

                                $searchLower = mb_strtolower($search);
                                $query = City::query()->where('country_id', $countryId);

                                // If region is selected, filter cities by region
                                if ($regionId) {
                                    $query->where('region_id', $regionId);
                                }

                                return $query
                                    ->where(function ($query) use ($searchLower) {
                                        $query->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.de"))) LIKE ?', ['%' . $searchLower . '%'])
                                              ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.en"))) LIKE ?', ['%' . $searchLower . '%']);
                                    })
                                    ->orderBy('name_translations->de')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(fn (City $c) => [$c->id => $c->getName('de')])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string =>
                                $value ? City::find($value)?->getName('de') : null
                            )
                            ->visible(fn ($get) => $get('recordId') !== null)
                            ->helperText(fn ($get) => $get('region_id')
                                ? 'Optional: Wählen Sie eine Stadt der ausgewählten Region'
                                : 'Optional: Wählen Sie eine Stadt des ausgewählten Landes'),

                        Toggle::make('use_default_coordinates')
                            ->label('Standard-Koordinaten verwenden')
                            ->helperText('Verwendet Koordinaten von Stadt > Region > Land')
                            ->default(true)
                            ->reactive()
                            ->afterStateUpdated(function ($get, $set, ?bool $state) {
                                if ($state) {
                                    // Priority: City > Region > Country
                                    if ($get('city_id')) {
                                        $city = City::find($get('city_id'));
                                        if ($city && $city->lat && $city->lng) {
                                            $set('latitude', $city->lat);
                                            $set('longitude', $city->lng);
                                            return;
                                        }
                                    }

                                    if ($get('region_id')) {
                                        $region = Region::find($get('region_id'));
                                        if ($region && $region->lat && $region->lng) {
                                            $set('latitude', $region->lat);
                                            $set('longitude', $region->lng);
                                            return;
                                        }
                                    }

                                    if ($get('recordId')) {
                                        $country = Country::find($get('recordId'));
                                        if ($country && $country->lat && $country->lng) {
                                            $set('latitude', $country->lat);
                                            $set('longitude', $country->lng);
                                        }
                                    }
                                }
                            }),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('latitude')
                                    ->label('Breitengrad')
                                    ->numeric()
                                    ->step('any')
                                    ->disabled(fn ($get) => (bool) $get('use_default_coordinates'))
                                    ->required(fn ($get) => !(bool) $get('use_default_coordinates'))
                                    ->placeholder('50.1109')
                                    ->prefix('Lat:'),

                                TextInput::make('longitude')
                                    ->label('Längengrad')
                                    ->numeric()
                                    ->step('any')
                                    ->disabled(fn ($get) => (bool) $get('use_default_coordinates'))
                                    ->required(fn ($get) => !(bool) $get('use_default_coordinates'))
                                    ->placeholder('8.6821')
                                    ->prefix('Lng:'),
                            ]),

                        TextInput::make('google_maps_coordinates')
                            ->label('Google Maps Koordinaten einfügen')
                            ->placeholder('z.B. 50.1109, 8.6821')
                            ->helperText('Koordinaten aus Google Maps hier einfügen - automatische Übernahme')
                            ->disabled(fn ($get) => (bool) $get('use_default_coordinates'))
                            ->live(onBlur: true)
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($set, $get, ?string $state) {
                                if (!$state || $get('use_default_coordinates')) {
                                    return;
                                }

                                // Parse Google Maps coordinate formats
                                $cleaned = preg_replace('/[^\d.,\-]/', ' ', $state);
                                $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));

                                if (strpos($cleaned, ',') !== false) {
                                    $parts = explode(',', $cleaned);
                                } else {
                                    $parts = explode(' ', $cleaned);
                                }

                                if (count($parts) >= 2) {
                                    $lat = trim($parts[0]);
                                    $lng = trim($parts[1]);

                                    if (is_numeric($lat) && is_numeric($lng)) {
                                        $set('latitude', $lat);
                                        $set('longitude', $lng);
                                    }
                                }
                            }),

                        Textarea::make('location_note')
                            ->label('Standort-Notiz')
                            ->rows(2)
                            ->placeholder('z.B. Hauptstadt, Flughafen Frankfurt, etc.')
                            ->helperText('Optional: Beschreiben Sie den genauen Standort'),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        // If using default coordinates, get them from city, region or country
                        if ($data['use_default_coordinates'] ?? true) {
                            // Priority: City > Region > Country
                            if (!empty($data['city_id'])) {
                                $city = City::find($data['city_id']);
                                if ($city && $city->lat && $city->lng) {
                                    $data['latitude'] = $city->lat;
                                    $data['longitude'] = $city->lng;
                                }
                            } elseif (!empty($data['region_id'])) {
                                $region = Region::find($data['region_id']);
                                if ($region && $region->lat && $region->lng) {
                                    $data['latitude'] = $region->lat;
                                    $data['longitude'] = $region->lng;
                                }
                            } else {
                                $country = Country::find($data['recordId']);
                                if ($country && $country->lat && $country->lng) {
                                    $data['latitude'] = $country->lat;
                                    $data['longitude'] = $country->lng;
                                }
                            }
                        }
                        return $data;
                    }),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->label('Bearbeiten')
                    ->modalHeading('Standort bearbeiten')
                    ->modalSubmitActionLabel('Speichern')
                    ->form([
                        Select::make('region_id')
                            ->label('Region (optional)')
                            ->options(function (Country $record) {
                                return Region::query()
                                    ->where('country_id', $record->id)
                                    ->orderBy('name_translations->de')
                                    ->get()
                                    ->mapWithKeys(fn (Region $r) => [$r->id => $r->getName('de')])
                                    ->toArray();
                            })
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($set) {
                                // Reset city when region changes
                                $set('city_id', null);
                            })
                            ->getSearchResultsUsing(function (string $search, Country $record) {
                                $searchLower = mb_strtolower($search);

                                return Region::query()
                                    ->where('country_id', $record->id)
                                    ->where(function ($query) use ($searchLower) {
                                        $query->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.de"))) LIKE ?', ['%' . $searchLower . '%'])
                                              ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.en"))) LIKE ?', ['%' . $searchLower . '%'])
                                              ->orWhereRaw('LOWER(code) LIKE ?', ['%' . $searchLower . '%']);
                                    })
                                    ->orderBy('name_translations->de')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(fn (Region $r) => [$r->id => $r->getName('de')])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string =>
                                $value ? Region::find($value)?->getName('de') : null
                            )
                            ->helperText('Optional: Wählen Sie eine Region des Landes'),

                        Select::make('city_id')
                            ->label('Stadt (optional)')
                            ->options(function ($get, Country $record) {
                                $regionId = $get('region_id');

                                $query = City::query()->where('country_id', $record->id);

                                // If region is selected, filter cities by region
                                if ($regionId) {
                                    $query->where('region_id', $regionId);
                                }

                                return $query
                                    ->orderBy('name_translations->de')
                                    ->get()
                                    ->mapWithKeys(fn (City $c) => [$c->id => $c->getName('de')])
                                    ->toArray();
                            })
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($get, $set, ?string $state) {
                                // Update coordinates if city is selected and using default coordinates
                                if ($state && $get('use_default_coordinates')) {
                                    $city = City::find($state);
                                    if ($city && $city->lat && $city->lng) {
                                        $set('latitude', $city->lat);
                                        $set('longitude', $city->lng);
                                    }
                                }
                            })
                            ->getSearchResultsUsing(function (string $search, $get, Country $record) {
                                $regionId = $get('region_id');
                                $searchLower = mb_strtolower($search);
                                $query = City::query()->where('country_id', $record->id);

                                // If region is selected, filter cities by region
                                if ($regionId) {
                                    $query->where('region_id', $regionId);
                                }

                                return $query
                                    ->where(function ($query) use ($searchLower) {
                                        $query->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.de"))) LIKE ?', ['%' . $searchLower . '%'])
                                              ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.en"))) LIKE ?', ['%' . $searchLower . '%']);
                                    })
                                    ->orderBy('name_translations->de')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(fn (City $c) => [$c->id => $c->getName('de')])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string =>
                                $value ? City::find($value)?->getName('de') : null
                            )
                            ->helperText(fn ($get) => $get('region_id')
                                ? 'Optional: Wählen Sie eine Stadt der ausgewählten Region'
                                : 'Optional: Wählen Sie eine Stadt des Landes'),

                        Toggle::make('use_default_coordinates')
                            ->label('Standard-Koordinaten verwenden')
                            ->helperText('Verwendet Koordinaten von Stadt > Region > Land')
                            ->reactive(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('latitude')
                                    ->label('Breitengrad')
                                    ->numeric()
                                    ->step('any')
                                    ->disabled(fn ($get) => (bool) $get('use_default_coordinates'))
                                    ->required(fn ($get) => !(bool) $get('use_default_coordinates'))
                                    ->prefix('Lat:'),

                                TextInput::make('longitude')
                                    ->label('Längengrad')
                                    ->numeric()
                                    ->step('any')
                                    ->disabled(fn ($get) => (bool) $get('use_default_coordinates'))
                                    ->required(fn ($get) => !(bool) $get('use_default_coordinates'))
                                    ->prefix('Lng:'),
                            ]),

                        TextInput::make('google_maps_coordinates')
                            ->label('Google Maps Koordinaten einfügen')
                            ->placeholder('z.B. 50.1109, 8.6821')
                            ->helperText('Koordinaten aus Google Maps hier einfügen - automatische Übernahme')
                            ->disabled(fn ($get) => (bool) $get('use_default_coordinates'))
                            ->live(onBlur: true)
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($set, $get, ?string $state) {
                                if (!$state || $get('use_default_coordinates')) {
                                    return;
                                }

                                $cleaned = preg_replace('/[^\d.,\-]/', ' ', $state);
                                $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));

                                if (strpos($cleaned, ',') !== false) {
                                    $parts = explode(',', $cleaned);
                                } else {
                                    $parts = explode(' ', $cleaned);
                                }

                                if (count($parts) >= 2) {
                                    $lat = trim($parts[0]);
                                    $lng = trim($parts[1]);

                                    if (is_numeric($lat) && is_numeric($lng)) {
                                        $set('latitude', $lat);
                                        $set('longitude', $lng);
                                    }
                                }
                            }),

                        Textarea::make('location_note')
                            ->label('Standort-Notiz')
                            ->rows(2)
                            ->placeholder('z.B. Hauptstadt, Flughafen Frankfurt, etc.'),
                    ])
                    ->mutateFormDataUsing(function (array $data, Country $record): array {
                        // If using default coordinates, get them from city, region or country
                        if ($data['use_default_coordinates'] ?? false) {
                            // Priority: City > Region > Country
                            if (!empty($data['city_id'])) {
                                $city = City::find($data['city_id']);
                                if ($city && $city->lat && $city->lng) {
                                    $data['latitude'] = $city->lat;
                                    $data['longitude'] = $city->lng;
                                }
                            } elseif (!empty($data['region_id'])) {
                                $region = Region::find($data['region_id']);
                                if ($region && $region->lat && $region->lng) {
                                    $data['latitude'] = $region->lat;
                                    $data['longitude'] = $region->lng;
                                }
                            } else {
                                $data['latitude'] = $record->lat;
                                $data['longitude'] = $record->lng;
                            }
                        }
                        return $data;
                    }),

                \Filament\Actions\DetachAction::make()
                    ->label('Entfernen')
                    ->modalHeading('Land vom Event entfernen')
                    ->modalDescription('Möchten Sie dieses Land wirklich vom Event entfernen?')
                    ->modalSubmitActionLabel('Ja, entfernen')
                    ->modalCancelActionLabel('Abbrechen'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DetachBulkAction::make()
                        ->label('Ausgewählte entfernen')
                        ->modalHeading('Länder vom Event entfernen')
                        ->modalDescription('Möchten Sie die ausgewählten Länder wirklich vom Event entfernen?')
                        ->modalSubmitActionLabel('Ja, entfernen')
                        ->modalCancelActionLabel('Abbrechen'),
                ]),
            ])
            ->defaultSort('name_translations->de')
            ->paginated([10, 25, 50, 100]);
    }
}