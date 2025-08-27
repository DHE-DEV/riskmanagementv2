<?php

namespace App\Filament\Resources\Airports\Pages;

use App\Filament\Resources\Airports\AirportResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;

class ViewAirport extends ViewRecord
{
    protected static string $resource = AirportResource::class;

    protected static ?string $title = 'Flughafen anzeigen';

    public function getTitle(): string
    {
        $airportName = $this->record->name ?? 'Unbekannter Flughafen';
        return "Flughafen: {$airportName}";
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Flughafen Details')
                    ->schema([
                        Placeholder::make('name')
                            ->label('Name')
                            ->content(fn ($record) => $record->name ?? 'Nicht verfügbar'),
                        Placeholder::make('iata_code')
                            ->label('IATA Code')
                            ->content(fn ($record) => $record->iata_code ?? 'Nicht verfügbar'),
                        Placeholder::make('icao_code')
                            ->label('ICAO Code')
                            ->content(fn ($record) => $record->icao_code ?? 'Nicht verfügbar'),
                        Placeholder::make('type')
                            ->label('Typ')
                            ->content(fn ($record) => match($record->type) {
                                'international' => 'Internationaler Flughafen',
                                'large_airport' => 'Großer Flughafen',
                                'medium_airport' => 'Mittlerer Flughafen',
                                'small_airport' => 'Kleiner Flughafen',
                                'heliport' => 'Hubschrauberlandeplatz',
                                'seaplane_base' => 'Wasserflugzeugbasis',
                                default => $record->type ?? 'Unbekannt'
                            }),
                    ])
                    ->columns(2),
                Section::make('Geografische Informationen')
                    ->schema([
                        Placeholder::make('country')
                            ->label('Land')
                            ->content(fn ($record) => $record->country ? $record->country->getName('de') : 'Nicht verfügbar'),
                        Placeholder::make('city')
                            ->label('Stadt')
                            ->content(fn ($record) => $record->city ? $record->city->getName('de') : 'Nicht verfügbar'),
                        Placeholder::make('coordinates')
                            ->label('Koordinaten')
                            ->content(fn ($record) => $record->lat && $record->lng ? "{$record->lat}, {$record->lng}" : 'Nicht verfügbar'),
                        Placeholder::make('altitude')
                            ->label('Höhe')
                            ->content(fn ($record) => $record->altitude ? $record->altitude . ' m' : 'Nicht verfügbar'),
                    ])
                    ->columns(2),
                Section::make('Zeitzone Informationen')
                    ->schema([
                        Placeholder::make('timezone')
                            ->label('Zeitzone')
                            ->content(fn ($record) => $record->timezone ?? 'Nicht verfügbar'),
                        Placeholder::make('dst_timezone')
                            ->label('Sommerzeit Zeitzone')
                            ->content(fn ($record) => $record->dst_timezone ?? 'Nicht verfügbar'),
                        Placeholder::make('source')
                            ->label('Datenquelle')
                            ->content(fn ($record) => $record->source ?? 'Nicht verfügbar'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }
}