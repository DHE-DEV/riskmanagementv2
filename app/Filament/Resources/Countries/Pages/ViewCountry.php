<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Resources\Countries\CountryResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;

class ViewCountry extends ViewRecord
{
    protected static string $resource = CountryResource::class;

    protected static ?string $title = 'Land anzeigen';

    public function getTitle(): string
    {
        $countryName = $this->record->getName('de');
        return "Land: {$countryName}";
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Land Details')
                    ->schema([
                        Placeholder::make('german_name')
                            ->label('Name (Deutsch)')
                            ->content(fn ($record) => $record->getName('de')),
                        Placeholder::make('english_name')
                            ->label('Name (Englisch)')
                            ->content(fn ($record) => $record->getName('en')),
                        Placeholder::make('iso_code')
                            ->label('ISO Code')
                            ->content(fn ($record) => $record->iso_code),
                        Placeholder::make('iso3_code')
                            ->label('ISO3 Code')
                            ->content(fn ($record) => $record->iso3_code),
                        Placeholder::make('is_eu_member')
                            ->label('EU-Mitglied')
                            ->content(fn ($record) => $record->is_eu_member ? 'Ja' : 'Nein'),
                        Placeholder::make('is_schengen_member')
                            ->label('Schengen-Mitglied')
                            ->content(fn ($record) => $record->is_schengen_member ? 'Ja' : 'Nein'),
                    ])
                    ->columns(3),
                Section::make('Geografische Informationen')
                    ->schema([
                        Placeholder::make('continent')
                            ->label('Kontinent')
                            ->content(fn ($record) => $record->continent ? ($record->continent->name_translations['de'] ?? $record->continent->name_translations['en'] ?? $record->continent->code) : 'Nicht verfügbar'),
                        Placeholder::make('coordinates')
                            ->label('Koordinaten')
                            ->content(fn ($record) => $record->lat && $record->lng ? "{$record->lat}, {$record->lng}" : 'Nicht verfügbar'),
                        Placeholder::make('population')
                            ->label('Bevölkerung')
                            ->content(fn ($record) => $record->population ? number_format($record->population) : 'Nicht verfügbar'),
                        Placeholder::make('area_km2')
                            ->label('Fläche (km²)')
                            ->content(fn ($record) => $record->area_km2 ? number_format($record->area_km2) : 'Nicht verfügbar'),
                    ])
                    ->columns(2),
                Section::make('Wirtschaftliche Informationen')
                    ->schema([
                        Placeholder::make('currency_code')
                            ->label('Währungscode')
                            ->content(fn ($record) => $record->currency_code ?? 'Nicht verfügbar'),
                        Placeholder::make('currency_name')
                            ->label('Währungsname')
                            ->content(fn ($record) => $record->currency_name ?? 'Nicht verfügbar'),
                        Placeholder::make('currency_symbol')
                            ->label('Währungssymbol')
                            ->content(fn ($record) => $record->currency_symbol ?? 'Nicht verfügbar'),
                        Placeholder::make('phone_prefix')
                            ->label('Telefonvorwahl')
                            ->content(fn ($record) => $record->phone_prefix ?? 'Nicht verfügbar'),
                        Placeholder::make('timezone')
                            ->label('Zeitzone')
                            ->content(fn ($record) => $record->timezone ?? 'Nicht verfügbar'),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }
}