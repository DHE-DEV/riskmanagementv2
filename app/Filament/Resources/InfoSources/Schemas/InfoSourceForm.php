<?php

namespace App\Filament\Resources\InfoSources\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class InfoSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Allgemein')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Auswärtiges Amt')
                                    ->columnSpan(1),

                                TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50)
                                    ->placeholder('auswaertiges-amt')
                                    ->helperText('Eindeutiger Identifier')
                                    ->columnSpan(1),

                                Textarea::make('description')
                                    ->label('Beschreibung')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->placeholder('Offizielle Reisewarnungen des deutschen Auswärtigen Amtes')
                                    ->columnSpanFull(),

                                Select::make('type')
                                    ->label('Typ')
                                    ->options([
                                        'rss' => 'RSS Feed',
                                        'api' => 'JSON API',
                                        'rss_api' => 'RSS + API',
                                    ])
                                    ->default('rss')
                                    ->required()
                                    ->columnSpan(1),

                                Select::make('content_type')
                                    ->label('Inhaltstyp')
                                    ->options([
                                        'travel_advisory' => 'Reisewarnungen',
                                        'health' => 'Gesundheit',
                                        'disaster' => 'Naturkatastrophen',
                                        'conflict' => 'Konflikte & Unruhen',
                                        'general' => 'Allgemein',
                                    ])
                                    ->default('general')
                                    ->required()
                                    ->columnSpan(1),

                                Select::make('country_code')
                                    ->label('Herkunftsland')
                                    ->options([
                                        'DE' => 'Deutschland',
                                        'US' => 'USA',
                                        'GB' => 'Großbritannien',
                                        'CH' => 'Schweiz',
                                        'AT' => 'Österreich',
                                    ])
                                    ->placeholder('International')
                                    ->columnSpan(1),

                                Select::make('language')
                                    ->label('Sprache')
                                    ->options([
                                        'de' => 'Deutsch',
                                        'en' => 'Englisch',
                                        'fr' => 'Französisch',
                                        'es' => 'Spanisch',
                                    ])
                                    ->default('en')
                                    ->required()
                                    ->columnSpan(1),
                            ])
                            ->columns(2),

                        Tab::make('Verbindung')
                            ->icon('heroicon-o-link')
                            ->schema([
                                TextInput::make('url')
                                    ->label('RSS-Feed URL')
                                    ->url()
                                    ->maxLength(500)
                                    ->placeholder('https://example.com/feed.rss')
                                    ->columnSpanFull(),

                                TextInput::make('api_endpoint')
                                    ->label('API-Endpunkt')
                                    ->url()
                                    ->maxLength(500)
                                    ->placeholder('https://api.example.com/v1/advisories')
                                    ->columnSpanFull(),

                                TextInput::make('api_key')
                                    ->label('API-Key')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(255)
                                    ->helperText('Wird verschlüsselt gespeichert')
                                    ->columnSpan(1),

                                TextInput::make('refresh_interval')
                                    ->label('Aktualisierungsintervall')
                                    ->numeric()
                                    ->default(3600)
                                    ->suffix('Sekunden')
                                    ->helperText('3600 = 1 Stunde')
                                    ->columnSpan(1),

                                KeyValue::make('api_config')
                                    ->label('API-Konfiguration')
                                    ->keyLabel('Parameter')
                                    ->valueLabel('Wert')
                                    ->addActionLabel('Parameter hinzufügen')
                                    ->helperText('Zusätzliche API-Parameter (Header, Query-Params, etc.)')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make('Status')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->helperText('Aktiviert den automatischen Abruf')
                                    ->default(false)
                                    ->columnSpan(1),

                                Toggle::make('auto_import')
                                    ->label('Auto-Import')
                                    ->helperText('Events automatisch importieren (ohne Überprüfung)')
                                    ->default(false)
                                    ->columnSpan(1),

                                TextInput::make('sort_order')
                                    ->label('Sortierung')
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
