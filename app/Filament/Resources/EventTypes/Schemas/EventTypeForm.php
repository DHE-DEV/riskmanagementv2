<?php

namespace App\Filament\Resources\EventTypes\Schemas;

use Filament\Forms\Components\ColorPicker;
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

                ColorPicker::make('color')
                    ->label('Farbe')
                    ->default('#3B82F6')
                    ->helperText('Farbe für die Darstellung in der UI')
                    ->columnSpan(1),

                Select::make('icon')
                    ->label('FontAwesome Icon')
                    ->options([
                        'fa-exclamation-triangle' => '⚠️ Warnung',
                        'fa-fire' => '🔥 Feuer',
                        'fa-tint' => '💧 Wasser/Überschwemmung',
                        'fa-wind' => '💨 Wind/Sturm',
                        'fa-bolt' => '⚡ Blitz',
                        'fa-mountain' => '⛰️ Berg/Vulkan',
                        'fa-tree' => '🌳 Wald',
                        'fa-globe' => '🌍 Global',
                        'fa-thermometer-half' => '🌡️ Temperatur',
                        'fa-cloud' => '☁️ Wetter',
                        'fa-snowflake' => '❄️ Schnee',
                        'fa-sun' => '☀️ Sonne/Hitze',
                        'fa-moon' => '🌙 Nacht',
                        'fa-car-crash' => '🚗 Unfall',
                        'fa-building' => '🏢 Gebäude',
                        'fa-home' => '🏠 Wohnen',
                        'fa-hospital' => '🏥 Medizin',
                        'fa-shield-alt' => '🛡️ Sicherheit',
                        'fa-tools' => '🔧 Wartung',
                        'fa-graduation-cap' => '🎓 Übung/Training',
                        'fa-map-marker' => '📍 Marker',
                        'fa-info-circle' => 'ℹ️ Information',
                        'fa-check-circle' => '✅ Bestätigung',
                        'fa-times-circle' => '❌ Fehler',
                        'fa-clock' => '🕐 Zeit',
                        'fa-calendar' => '📅 Termin',
                    ])
                    ->default('fa-exclamation-triangle')
                    ->searchable()
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
}