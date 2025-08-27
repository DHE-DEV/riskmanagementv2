<?php

namespace App\Filament\Resources\Continents\Pages;

use App\Filament\Resources\Continents\ContinentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;

class ViewContinent extends ViewRecord
{
    protected static string $resource = ContinentResource::class;

    protected static ?string $title = 'Kontinent anzeigen';

    public function getTitle(): string
    {
        $continentName = $this->record->name_translations['de'] ?? $this->record->name_translations['en'] ?? $this->record->code;
        return "Kontinent: {$continentName}";
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Kontinent Details')
                    ->schema([
                        Placeholder::make('german_name')
                            ->label('Name (Deutsch)')
                            ->content(fn ($record) => $record->name_translations['de'] ?? 'Nicht verfügbar'),
                        Placeholder::make('english_name')
                            ->label('Name (Englisch)')
                            ->content(fn ($record) => $record->name_translations['en'] ?? 'Nicht verfügbar'),
                        Placeholder::make('code')
                            ->label('Code')
                            ->content(fn ($record) => $record->code),
                        Placeholder::make('countries_count')
                            ->label('Anzahl Länder')
                            ->content(fn ($record) => $record->countries()->count()),
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