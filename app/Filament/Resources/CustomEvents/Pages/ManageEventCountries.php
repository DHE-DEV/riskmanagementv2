<?php

namespace App\Filament\Resources\CustomEvents\Pages;

use App\Filament\Resources\CustomEvents\CustomEventResource;
use App\Models\Country;
use App\Models\CustomEvent;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;

class ManageEventCountries extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = CustomEventResource::class;

    protected static ?string $title = 'Länder & Standorte verwalten';

    public CustomEvent $record;

    public array $countryLocations = [];

    public function mount($record): void
    {
        $this->record = CustomEvent::findOrFail($record);

        // Load existing country locations
        $this->countryLocations = $this->record->countries->map(function ($country) {
            return [
                'country_id' => $country->id,
                'latitude' => $country->pivot->latitude ?? $country->lat,
                'longitude' => $country->pivot->longitude ?? $country->lng,
                'location_note' => $country->pivot->location_note,
                'use_default_coordinates' => $country->pivot->use_default_coordinates,
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
                ->description('Fügen Sie beliebig viele Länder hinzu. Pro Land können Sie eigene Koordinaten angeben oder die Standard-Koordinaten verwenden.')
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
                                            if ($state) {
                                                $country = Country::find($state);
                                                if ($country && $country->lat && $country->lng) {
                                                    $set('latitude', $country->lat);
                                                    $set('longitude', $country->lng);
                                                }
                                            }
                                        })
                                        ->columnSpan(2),

                                    Toggle::make('use_default_coordinates')
                                        ->label('Standard-Koordinaten der Hauptstadt des Landes verwenden')
                                        ->default(true)
                                        ->reactive()
                                        ->afterStateUpdated(function (Get $get, Set $set, ?bool $state) {
                                            if ($state && $get('country_id')) {
                                                $country = Country::find($get('country_id'));
                                                if ($country && $country->lat && $country->lng) {
                                                    $set('latitude', $country->lat);
                                                    $set('longitude', $country->lng);
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
                        ->itemLabel(function (array $state): ?string {
                            if ($state['country_id'] ?? null) {
                                $country = Country::find($state['country_id']);
                                $label = $country ? $country->getName('de') : 'Unbekannt';
                                if ($state['location_note'] ?? null) {
                                    $label .= ' - ' . $state['location_note'];
                                }
                                return $label;
                            }
                            return null;
                        })
                        ->columns(1),
                ]),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('save')
                ->label('Speichern')
                ->color('primary')
                ->action('save'),

            Action::make('cancel')
                ->label('Abbrechen')
                ->color('gray')
                ->url(CustomEventResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Prepare sync data
        $syncData = [];
        foreach ($data['countryLocations'] as $location) {
            // Use default coordinates if toggle is on
            if ($location['use_default_coordinates']) {
                $country = Country::find($location['country_id']);
                $location['latitude'] = $country->lat;
                $location['longitude'] = $country->lng;
            }

            $syncData[$location['country_id']] = [
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
                'location_note' => $location['location_note'] ?? null,
                'use_default_coordinates' => $location['use_default_coordinates'] ?? true,
            ];
        }

        // Sync countries with pivot data
        $this->record->countries()->sync($syncData);

        Notification::make()
            ->title('Länder und Standorte wurden aktualisiert')
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