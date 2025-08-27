<?php

namespace App\Filament\Resources\Cities\Pages;

use App\Filament\Resources\Cities\CityResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;

class ViewCity extends ViewRecord
{
    protected static string $resource = CityResource::class;

    protected static ?string $title = 'Stadt anzeigen';

    public function getTitle(): string
    {
        $cityName = $this->record->getName('de');
        return "Stadt: {$cityName}";
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Stadt Details')
                    ->schema([
                        Placeholder::make('german_name')
                            ->label('Name (Deutsch)')
                            ->content(fn ($record) => $record->getName('de')),
                        Placeholder::make('english_name')
                            ->label('Name (Englisch)')
                            ->content(fn ($record) => $record->getName('en')),
                        Placeholder::make('is_capital')
                            ->label('Hauptstadt')
                            ->content(fn ($record) => $record->is_capital ? 'Ja' : 'Nein'),
                        Placeholder::make('population')
                            ->label('Bevölkerung')
                            ->content(fn ($record) => $record->population ? number_format($record->population) : 'Nicht verfügbar'),
                    ])
                    ->columns(2),
                Section::make('Geografische Informationen')
                    ->schema([
                        Placeholder::make('country')
                            ->label('Land')
                            ->content(fn ($record) => $record->country ? $record->country->getName('de') : 'Nicht verfügbar'),
                        Placeholder::make('region')
                            ->label('Region')
                            ->content(fn ($record) => $record->region ? $record->region->getName('de') : 'Nicht verfügbar'),
                        Placeholder::make('coordinates')
                            ->label('Koordinaten')
                            ->content(fn ($record) => $record->lat && $record->lng ? "{$record->lat}, {$record->lng}" : 'Nicht verfügbar'),
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