<?php

namespace App\Filament\Resources\CustomEvents\RelationManagers;

use App\Models\Country;
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
                            ->afterStateUpdated(function (Set $set, ?string $state) {
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
                            ->label('Standard-Koordinaten des Landes verwenden')
                            ->default(true)
                            ->reactive()
                            ->afterStateUpdated(function (Get $get, Set $set, ?bool $state) {
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

                Tables\Columns\TextColumn::make('coordinates')
                    ->label('Koordinaten')
                    ->getStateUsing(function ($record) {
                        if (!$record || !$record->pivot) {
                            return '-';
                        }

                        $lat = $record->pivot->use_default_coordinates ? $record->lat : $record->pivot->latitude;
                        $lng = $record->pivot->use_default_coordinates ? $record->lng : $record->pivot->longitude;

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

                        $lat = $record->pivot->use_default_coordinates ? $record->lat : $record->pivot->latitude;
                        $lng = $record->pivot->use_default_coordinates ? $record->lng : $record->pivot->longitude;

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
                        return $query->orderBy('name_translations->de');
                    })
                    ->form(fn (\Filament\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Land auswählen')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) use ($action) {
                                // Get the owner record from the relationship manager
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

                        Toggle::make('use_default_coordinates')
                            ->label('Standard-Koordinaten des Landes verwenden')
                            ->default(true)
                            ->reactive(),

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
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        // If using default coordinates, get them from the country
                        if ($data['use_default_coordinates'] ?? true) {
                            $country = Country::find($data['recordId']);
                            if ($country) {
                                $data['latitude'] = $country->lat;
                                $data['longitude'] = $country->lng;
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
                        Toggle::make('use_default_coordinates')
                            ->label('Standard-Koordinaten des Landes verwenden')
                            ->reactive(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('latitude')
                                    ->label('Breitengrad')
                                    ->numeric()
                                    ->minValue(-90)
                                    ->maxValue(90)
                                    ->step('any')
                                    ->disabled(fn (SchemaGet $get): bool => (bool) $get('use_default_coordinates'))
                                    ->required(fn (SchemaGet $get): bool => !(bool) $get('use_default_coordinates')),

                                TextInput::make('longitude')
                                    ->label('Längengrad')
                                    ->numeric()
                                    ->minValue(-180)
                                    ->maxValue(180)
                                    ->step('any')
                                    ->disabled(fn (SchemaGet $get): bool => (bool) $get('use_default_coordinates'))
                                    ->required(fn (SchemaGet $get): bool => !(bool) $get('use_default_coordinates')),
                            ]),

                        Textarea::make('location_note')
                            ->label('Standort-Notiz')
                            ->rows(2)
                            ->placeholder('z.B. Hauptstadt, Flughafen Frankfurt, etc.'),
                    ])
                    ->mutateFormDataUsing(function (array $data, Country $record): array {
                        // If using default coordinates, get them from the country
                        if ($data['use_default_coordinates'] ?? false) {
                            $data['latitude'] = $record->lat;
                            $data['longitude'] = $record->lng;
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