<?php

namespace App\Filament\Resources\EventDisplaySettings\Schemas;

use App\Models\EventDisplaySetting;
use App\Models\EventType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EventDisplaySettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Multi-Event Icon Strategie')
                    ->description('Konfigurieren Sie, wie Icons angezeigt werden, wenn ein Event mehrere Event-Typen hat.')
                    ->schema([
                        Select::make('multi_event_icon_strategy')
                            ->label('Icon-Strategie')
                            ->options(EventDisplaySetting::getStrategyOptions())
                            ->default('default')
                            ->required()
                            ->live()
                            ->helperText('Wählen Sie, wie Icons bei Multi-Event-Auswahl dargestellt werden sollen'),

                        Select::make('multi_event_type_id')
                            ->label('Multi-Event Typ')
                            ->options(fn () => EventType::active()
                                ->ordered()
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('multi_event_icon_strategy') === 'multi_event_type')
                            ->required(fn (Get $get): bool => $get('multi_event_icon_strategy') === 'multi_event_type')
                            ->helperText('Wählen Sie den speziellen Event-Typ für Multi-Events (z.B. Event-Typ 15)'),

                        Toggle::make('show_icon_preview_in_form')
                            ->label('Icon-Vorschau im Formular anzeigen')
                            ->default(true)
                            ->helperText('Zeigt eine visuelle Vorschau aller gewählten Event-Icons im Formular an'),
                    ]),

                Section::make('Zusätzliche Informationen')
                    ->schema([
                        Textarea::make('strategy_description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->helperText('Optional: Notizen zur gewählten Strategie')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }
}
