<?php

namespace App\Filament\Resources\EventTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EventTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->placeholder('earthquake')
                    ->helperText('Eindeutiger Code für den Event-Typ (z.B. earthquake, flood)')
                    ->columnSpan(1),

                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('Erdbeben')
                    ->columnSpan(1),

                Textarea::make('description')
                    ->label('Beschreibung')
                    ->rows(3)
                    ->maxLength(500)
                    ->placeholder('Beschreibung des Event-Typs...')
                    ->columnSpanFull(),

                Select::make('icon')
                    ->label('FontAwesome Icon')
                    ->options(self::getFontAwesomeIconOptions())
                    ->default('fa-exclamation-triangle')
                    ->searchable()
                    ->allowHtml()
                    ->columnSpan(1),

                TextInput::make('sort_order')
                    ->label('Sortierung')
                    ->numeric()
                    ->default(0)
                    ->helperText('Niedrigere Zahlen werden zuerst angezeigt')
                    ->columnSpan(1),

                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true)
                    ->helperText('Nur aktive Event-Typen sind in Auswahlmenüs verfügbar')
                    ->columnSpan(1),
            ]);
    }

    /**
     * Gibt FontAwesome Icon Optionen mit echten Icons zurück
     */
    private static function getFontAwesomeIconOptions(): array
    {
        return [
            'fa-exclamation-triangle' => '<i class="fa fa-exclamation-triangle"></i> Warnung',
            'fa-fire' => '<i class="fa fa-fire"></i> Feuer',
            'fa-tint' => '<i class="fa fa-tint"></i> Wasser/Überschwemmung',
            'fa-wind' => '<i class="fa fa-wind"></i> Wind/Sturm',
            'fa-bolt' => '<i class="fa fa-bolt"></i> Blitz',
            'fa-mountain' => '<i class="fa fa-mountain"></i> Berg/Vulkan',
            'fa-tree' => '<i class="fa fa-tree"></i> Wald',
            'fa-globe' => '<i class="fa fa-globe"></i> Global',
            'fa-thermometer-half' => '<i class="fa fa-thermometer-half"></i> Temperatur',
            'fa-cloud' => '<i class="fa fa-cloud"></i> Wetter',
            'fa-snowflake' => '<i class="fa fa-snowflake"></i> Schnee',
            'fa-sun' => '<i class="fa fa-sun"></i> Sonne/Hitze',
            'fa-moon' => '<i class="fa fa-moon"></i> Nacht',
            'fa-car-crash' => '<i class="fa fa-car-crash"></i> Unfall',
            'fa-building' => '<i class="fa fa-building"></i> Gebäude',
            'fa-home' => '<i class="fa fa-home"></i> Wohnen',
            'fa-hospital' => '<i class="fa fa-hospital"></i> Medizin',
            'fa-shield-alt' => '<i class="fa fa-shield-alt"></i> Sicherheit',
            'fa-tools' => '<i class="fa fa-tools"></i> Wartung',
            'fa-graduation-cap' => '<i class="fa fa-graduation-cap"></i> Übung/Training',
            'fa-map-marker' => '<i class="fa fa-map-marker"></i> Marker',
            'fa-info-circle' => '<i class="fa fa-info-circle"></i> Information',
            'fa-check-circle' => '<i class="fa fa-check-circle"></i> Bestätigung',
            'fa-times-circle' => '<i class="fa fa-times-circle"></i> Fehler',
            'fa-clock' => '<i class="fa fa-clock"></i> Zeit',
            'fa-calendar' => '<i class="fa fa-calendar"></i> Termin',
            // Weitere disaster-spezifische Icons
            'fa-house-crack' => '<i class="fa fa-house-crack"></i> Erdbeben',
            'fa-water' => '<i class="fa fa-water"></i> Überschwemmung',
            'fa-volcano' => '<i class="fa fa-volcano"></i> Vulkanausbruch',
            'fa-hurricane' => '<i class="fa fa-hurricane"></i> Hurrikan',
            'fa-tornado' => '<i class="fa fa-tornado"></i> Tornado',
            'fa-snowplow' => '<i class="fa fa-snowplow"></i> Schneesturm',
            'fa-radiation' => '<i class="fa fa-radiation"></i> Nuklear',
            'fa-biohazard' => '<i class="fa fa-biohazard"></i> Biohazard',
            'fa-virus' => '<i class="fa fa-virus"></i> Pandemie',
            'fa-truck-medical' => '<i class="fa fa-truck-medical"></i> Medizinischer Notfall',
            'fa-person-falling' => '<i class="fa fa-person-falling"></i> Unfall',
            'fa-road-barrier' => '<i class="fa fa-road-barrier"></i> Straßensperrung',
            'fa-plane-slash' => '<i class="fa fa-plane-slash"></i> Flugausfall',
            'fa-train' => '<i class="fa fa-train"></i> Zug/Transport',
        ];
    }
}