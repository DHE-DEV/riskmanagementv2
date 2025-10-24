<?php

namespace App\Filament\Resources\EntryConditionsLogs\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;

class EntryConditionsLogForm
{
    public static function configure(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Allgemeine Informationen')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('ID'),
                                TextEntry::make('created_at')
                                    ->label('Datum/Uhrzeit')
                                    ->dateTime('d.m.Y H:i:s'),
                                TextEntry::make('nationality')
                                    ->label('Nationalität')
                                    ->badge(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('success')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Erfolgreich' : 'Fehlgeschlagen')
                                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                                TextEntry::make('results_count')
                                    ->label('Anzahl Ergebnisse')
                                    ->default('-'),
                            ]),
                    ]),

                Section::make('Aktivierte Filter')
                    ->schema([
                        KeyValueEntry::make('filters')
                            ->label('')
                            ->keyLabel('Filter')
                            ->valueLabel('Aktiviert')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return [];
                                }
                                $formatted = [];
                                foreach ($state as $key => $value) {
                                    if ($value === true) {
                                        $label = match($key) {
                                            'passport' => 'Reisepass',
                                            'idCard' => 'Personalausweis',
                                            'tempPassport' => 'Vorläufiger Reisepass',
                                            'tempIdCard' => 'Vorläufiger Personalausweis',
                                            'childPassport' => 'Kinderreisepass',
                                            'visaFree' => 'Einreise ohne Visum möglich',
                                            'eVisa' => 'E-Visum',
                                            'visaOnArrival' => 'Visum bei Ankunft',
                                            'noInsurance' => 'Keine Versicherung erforderlich',
                                            'noEntryForm' => 'Kein Einreiseformular erforderlich',
                                            default => $key
                                        };
                                        $formatted[$label] = 'Ja';
                                    }
                                }
                                return $formatted;
                            }),
                    ]),

                Section::make('API Request')
                    ->schema([
                        TextEntry::make('request_body')
                            ->label('Request Body (JSON)')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->copyable()
                            ->copyMessage('Request Body kopiert!')
                            ->markdown()
                            ->prose(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('API Response')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('response_status')
                                    ->label('HTTP Status Code')
                                    ->badge()
                                    ->color(fn ($state) => match(true) {
                                        $state >= 200 && $state < 300 => 'success',
                                        $state >= 400 && $state < 500 => 'warning',
                                        $state >= 500 => 'danger',
                                        default => 'gray'
                                    }),
                                TextEntry::make('error_message')
                                    ->label('Fehlermeldung')
                                    ->default('-')
                                    ->color('danger'),
                            ]),
                        TextEntry::make('response_data')
                            ->label('Response Data (JSON)')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->copyable()
                            ->copyMessage('Response Data kopiert!')
                            ->markdown()
                            ->prose()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
