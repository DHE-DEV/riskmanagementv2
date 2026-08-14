<?php

namespace App\Filament\Resources\CustomEvents\RelationManagers;

use App\Filament\Resources\CustomEvents\CustomEventResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CountriesRelationManager extends RelationManager
{
    protected static string $relationship = 'countries';

    protected static ?string $title = 'Länder & Standorte';

    protected static ?string $modelLabel = 'Land/Standort';

    protected static ?string $pluralModelLabel = 'Länder/Standorte';

    /**
     * Das Formular wird hier nicht mehr benoetigt - Anlegen und Bearbeiten laufen
     * ueber die Seite "Laender & Standorte verwalten" (ManageEventCountries),
     * weil dort pro Land beliebig viele Standort-Datensaetze moeglich sind.
     */
    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
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
            // Erfassung laeuft nicht mehr im Modal, sondern auf der eigenen Seite
            // "Laender & Standorte verwalten" (ManageEventCountries). Dort sind pro Land
            // beliebig viele Standort-Datensaetze moeglich.
            ->headerActions([
                \Filament\Actions\Action::make('manageLocations')
                    ->label('Standorte verwalten')
                    ->icon('heroicon-o-map-pin')
                    ->url(fn (): string => CustomEventResource::getUrl('manage-countries', [
                        'record' => $this->getOwnerRecord(),
                    ])),
            ])
            ->actions([
                \Filament\Actions\Action::make('editLocation')
                    ->label('Bearbeiten')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (): string => CustomEventResource::getUrl('manage-countries', [
                        'record' => $this->getOwnerRecord(),
                    ])),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Noch keine Standorte erfasst')
            ->emptyStateDescription('Legen Sie Länder, Regionen, Städte und Koordinaten auf der Standort-Seite an.')
            ->emptyStateActions([
                \Filament\Actions\Action::make('manageLocationsEmpty')
                    ->label('Standorte verwalten')
                    ->icon('heroicon-o-map-pin')
                    ->url(fn (): string => CustomEventResource::getUrl('manage-countries', [
                        'record' => $this->getOwnerRecord(),
                    ])),
            ])
            ->defaultSort('name_translations->de')
            ->paginated([10, 25, 50, 100]);
    }
}