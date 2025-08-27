<?php

namespace App\Filament\Resources\Regions\Pages;

use App\Filament\Resources\Regions\RegionResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;

class ViewRegion extends ViewRecord
{
    protected static string $resource = RegionResource::class;

    protected static ?string $title = 'Region anzeigen';

    public function getTitle(): string
    {
        $regionName = $this->record->getName('de');
        return "Region: {$regionName}";
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Region Details')
                    ->schema([
                        Placeholder::make('german_name')
                            ->label('Name (Deutsch)')
                            ->content(fn ($record) => $record->getName('de')),
                        Placeholder::make('english_name')
                            ->label('Name (Englisch)')
                            ->content(fn ($record) => $record->getName('en')),
                        Placeholder::make('code')
                            ->label('Code')
                            ->content(fn ($record) => $record->code ?? 'Nicht verfügbar'),
                        Placeholder::make('cities_count')
                            ->label('Anzahl Städte')
                            ->content(fn ($record) => $record->cities()->count()),
                    ])
                    ->columns(2),
                Section::make('Geografische Informationen')
                    ->schema([
                        Placeholder::make('country')
                            ->label('Land')
                            ->content(fn ($record) => $record->country ? $record->country->getName('de') : 'Nicht verfügbar'),
                        Placeholder::make('coordinates')
                            ->label('Koordinaten')
                            ->content(fn ($record) => $record->lat && $record->lng ? "{$record->lat}, {$record->lng}" : 'Nicht verfügbar'),
                        Placeholder::make('description')
                            ->label('Beschreibung')
                            ->content(fn ($record) => $record->description ?? 'Nicht verfügbar'),
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