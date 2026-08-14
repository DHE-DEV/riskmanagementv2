<?php

namespace App\Filament\Resources\CustomEvents\Pages;

use App\Filament\Resources\CustomEvents\CustomEventResource;
use App\Models\City;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ManageEventCountries extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithRecord;

    protected static string $resource = CustomEventResource::class;

    protected static ?string $title = 'Länder & Standorte verwalten';

    public array $countryLocations = [];

    public function mount(int | string $record): void
    {
        // Route-Model-Binding ueber die Resource - $record ist der Schluessel aus der URL.
        $this->record = $this->resolveRecord($record);

        // Alle Standort-Datensaetze laden - pro Land sind beliebig viele moeglich.
        $this->countryLocations = $this->record->countries->map(function ($country) {
            return [
                'country_id' => $country->id,
                'region_id' => $country->pivot->region_id,
                'city_id' => $country->pivot->city_id,
                'latitude' => $country->pivot->latitude ?? $country->lat,
                'longitude' => $country->pivot->longitude ?? $country->lng,
                'location_note' => $country->pivot->location_note,
                'use_default_coordinates' => (bool) $country->pivot->use_default_coordinates,
            ];
        })->toArray();

        $this->form->fill([
            'countryLocations' => $this->countryLocations,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Länder und Standorte')
                ->description('Fügen Sie beliebig viele Standort-Datensätze hinzu. Dasselbe Land darf mehrfach vorkommen – so lassen sich mehrere Regionen, Städte oder Koordinaten zu einem Land erfassen.')
                ->schema([
                    Repeater::make('countryLocations')
                        ->label('')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('country_id')
                                        ->label('Land')
                                        ->options(fn () => Country::query()
                                            ->orderBy('name_translations->de')
                                            ->get()
                                            ->mapWithKeys(fn (Country $c) => [$c->id => $c->getName('de') . ' (' . $c->iso_code . ')'])
                                            ->toArray()
                                        )
                                        ->searchable()
                                        ->required()
                                        ->preload()
                                        ->reactive()
                                        ->afterStateUpdated(function (Set $set, ?string $state) {
                                            // Region und Stadt gehoeren zum Land - beim Wechsel zuruecksetzen.
                                            $set('region_id', null);
                                            $set('city_id', null);

                                            if ($state) {
                                                $country = Country::find($state);
                                                if ($country && $country->lat && $country->lng) {
                                                    $set('latitude', $country->lat);
                                                    $set('longitude', $country->lng);
                                                }
                                            }
                                        })
                                        ->columnSpan(2),

                                    Select::make('region_id')
                                        ->label('Region (optional)')
                                        ->options(function (Get $get) {
                                            $countryId = $get('country_id');

                                            if (! $countryId) {
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
                                        ->preload()
                                        ->reactive()
                                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                            // Stadt haengt an der Region - beim Wechsel zuruecksetzen.
                                            $set('city_id', null);

                                            if ($state && $get('use_default_coordinates')) {
                                                $region = Region::find($state);
                                                if ($region && $region->lat && $region->lng) {
                                                    $set('latitude', $region->lat);
                                                    $set('longitude', $region->lng);
                                                }
                                            }
                                        })
                                        ->helperText('Optional: Region des ausgewählten Landes')
                                        ->columnSpan(1),

                                    Select::make('city_id')
                                        ->label('Stadt (optional)')
                                        ->options(function (Get $get) {
                                            $countryId = $get('country_id');

                                            if (! $countryId) {
                                                return [];
                                            }

                                            $query = City::query()->where('country_id', $countryId);

                                            if ($regionId = $get('region_id')) {
                                                $query->where('region_id', $regionId);
                                            }

                                            return $query
                                                ->orderBy('name_translations->de')
                                                ->limit(500)
                                                ->get()
                                                ->mapWithKeys(fn (City $c) => [$c->id => $c->getName('de')])
                                                ->toArray();
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->reactive()
                                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                            if ($state && $get('use_default_coordinates')) {
                                                $city = City::find($state);
                                                if ($city && $city->lat && $city->lng) {
                                                    $set('latitude', $city->lat);
                                                    $set('longitude', $city->lng);
                                                }
                                            }
                                        })
                                        ->helperText(fn (Get $get) => $get('region_id')
                                            ? 'Optional: Stadt der ausgewählten Region'
                                            : 'Optional: Stadt des ausgewählten Landes')
                                        ->columnSpan(1),

                                    Toggle::make('use_default_coordinates')
                                        ->label('Standard-Koordinaten verwenden')
                                        ->helperText('Kaskade: Stadt > Region > Hauptstadt > Land')
                                        ->default(true)
                                        ->reactive()
                                        ->afterStateUpdated(function (Get $get, Set $set, ?bool $state) {
                                            if ($state) {
                                                $coords = self::defaultCoordinatesFor(
                                                    $get('country_id'),
                                                    $get('region_id'),
                                                    $get('city_id'),
                                                );

                                                if ($coords) {
                                                    $set('latitude', $coords[0]);
                                                    $set('longitude', $coords[1]);
                                                }
                                            }
                                            // Clear Google Maps field when toggling
                                            $set('google_maps_coordinates', null);
                                        })
                                        ->columnSpan(2),

                                    TextInput::make('latitude')
                                        ->label('Breitengrad')
                                        ->numeric()
                                        ->step('any')
                                        ->disabled(fn (Get $get): bool => (bool) $get('use_default_coordinates'))
                                        ->required(fn (Get $get): bool => !(bool) $get('use_default_coordinates'))
                                        ->placeholder('50.1109')
                                        ->prefix('Lat:'),

                                    TextInput::make('longitude')
                                        ->label('Längengrad')
                                        ->numeric()
                                        ->step('any')
                                        ->disabled(fn (Get $get): bool => (bool) $get('use_default_coordinates'))
                                        ->required(fn (Get $get): bool => !(bool) $get('use_default_coordinates'))
                                        ->placeholder('8.6821')
                                        ->prefix('Lng:'),

                                    TextInput::make('google_maps_coordinates')
                                        ->label('Google Maps Koordinaten einfügen (Lat, Lng)')
                                        ->placeholder('z.B. 50.1109, 8.6821')
                                        ->helperText('Koordinaten aus Google Maps hier einfügen - automatische Übernahme in Breiten- und Längengrad')
                                        ->live(onBlur: true)
                                        ->dehydrated(false)
                                        ->disabled(fn (Get $get): bool => (bool) $get('use_default_coordinates'))
                                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                            if (!$state || $get('use_default_coordinates')) {
                                                return;
                                            }

                                            // Parse different Google Maps coordinate formats
                                            // Examples: "50.1109, 8.6821", "50.1109,8.6821", "50.1109 8.6821"
                                            $cleaned = preg_replace('/[^\d.,\-]/', ' ', $state);
                                            $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));

                                            // Try comma separator first
                                            if (strpos($cleaned, ',') !== false) {
                                                $parts = explode(',', $cleaned);
                                            } else {
                                                // Try space separator
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
                                        })
                                        ->columnSpan(2),

                                    Textarea::make('location_note')
                                        ->label('Standort-Notiz')
                                        ->rows(2)
                                        ->placeholder('z.B. Hauptstadt, Flughafen Frankfurt, etc.')
                                        ->columnSpan(2),
                                ]),
                        ])
                        ->addActionLabel('Land/Standort hinzufügen')
                        ->reorderable()
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(function (array $state): ?string {
                            if (! ($state['country_id'] ?? null)) {
                                return null;
                            }

                            $parts = array_filter([
                                Country::find($state['country_id'])?->getName('de') ?? 'Unbekannt',
                                ($state['region_id'] ?? null) ? Region::find($state['region_id'])?->getName('de') : null,
                                ($state['city_id'] ?? null) ? City::find($state['city_id'])?->getName('de') : null,
                            ]);

                            $label = implode(' – ', $parts);

                            if ($state['location_note'] ?? null) {
                                $label .= ' (' . $state['location_note'] . ')';
                            }

                            return $label;
                        })
                        ->columns(1),
                ]),
        ];
    }

    // Speichern/Abbrechen liegen im Blade-View der Seite (manage-event-countries.blade.php).

    /**
     * Standard-Koordinaten nach Kaskade Stadt > Region > Hauptstadt > Land.
     *
     * @return array{0: float|string, 1: float|string}|null
     */
    protected static function defaultCoordinatesFor($countryId, $regionId = null, $cityId = null): ?array
    {
        if ($cityId) {
            $city = City::find($cityId);
            if ($city && $city->lat && $city->lng) {
                return [$city->lat, $city->lng];
            }
        }

        if ($regionId) {
            $region = Region::find($regionId);
            if ($region && $region->lat && $region->lng) {
                return [$region->lat, $region->lng];
            }
        }

        if ($countryId) {
            $country = Country::with('capital')->find($countryId);

            if ($country?->capital && $country->capital->lat && $country->capital->lng) {
                return [$country->capital->lat, $country->capital->lng];
            }

            if ($country && $country->lat && $country->lng) {
                return [$country->lat, $country->lng];
            }
        }

        return null;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $rows = [];
        $now = now();

        foreach ($data['countryLocations'] ?? [] as $location) {
            if (empty($location['country_id'])) {
                continue;
            }

            $useDefault = (bool) ($location['use_default_coordinates'] ?? true);
            $latitude = $location['latitude'] ?? null;
            $longitude = $location['longitude'] ?? null;

            if ($useDefault) {
                $coords = self::defaultCoordinatesFor(
                    $location['country_id'],
                    $location['region_id'] ?? null,
                    $location['city_id'] ?? null,
                );

                $latitude = $coords[0] ?? null;
                $longitude = $coords[1] ?? null;
            }

            $rows[] = [
                'custom_event_id' => $this->record->id,
                'country_id' => $location['country_id'],
                'region_id' => $location['region_id'] ?? null,
                'city_id' => $location['city_id'] ?? null,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'location_note' => $location['location_note'] ?? null,
                'use_default_coordinates' => $useDefault,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Bewusst kein sync(): sync() ist auf einen Datensatz je Land beschraenkt.
        // Ein Land darf hier mehrfach vorkommen (mehrere Regionen/Staedte/Koordinaten).
        DB::transaction(function () use ($rows) {
            DB::table('country_custom_event')
                ->where('custom_event_id', $this->record->id)
                ->delete();

            if (! empty($rows)) {
                DB::table('country_custom_event')->insert($rows);
            }
        });

        $this->record->unsetRelation('countries');

        // Die Inserts oben laufen an Eloquent vorbei - touch() stoesst den Observer an,
        // damit gtm_all_events und die Feed-Caches sofort verworfen werden.
        $this->record->touch();

        Notification::make()
            ->title(count($rows) === 1
                ? '1 Standort-Datensatz gespeichert'
                : count($rows) . ' Standort-Datensätze gespeichert')
            ->success()
            ->send();

        $this->redirect(CustomEventResource::getUrl('edit', ['record' => $this->record]));
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getBreadcrumbs(): array
    {
        return [
            CustomEventResource::getUrl() => CustomEventResource::getPluralModelLabel(),
            CustomEventResource::getUrl('edit', ['record' => $this->record]) => $this->record->title,
            '#' => 'Länder & Standorte verwalten',
        ];
    }

    protected function getViewData(): array
    {
        return [];
    }

    public function getView(): string
    {
        return 'filament.resources.custom-events.pages.manage-event-countries';
    }
}