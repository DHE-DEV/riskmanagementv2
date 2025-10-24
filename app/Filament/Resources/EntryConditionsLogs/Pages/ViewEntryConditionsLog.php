<?php

namespace App\Filament\Resources\EntryConditionsLogs\Pages;

use App\Filament\Resources\EntryConditionsLogs\EntryConditionsLogResource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewEntryConditionsLog extends ViewRecord
{
    protected static string $resource = EntryConditionsLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Allgemeine Informationen')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('id')
                                    ->label('ID')
                                    ->content(fn ($record) => $record->id),
                                Placeholder::make('created_at')
                                    ->label('Datum/Uhrzeit')
                                    ->content(fn ($record) => $record->created_at->format('d.m.Y H:i:s')),
                                Placeholder::make('nationality')
                                    ->label('Nationalität')
                                    ->content(fn ($record) => $record->nationality),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('success')
                                    ->label('Status')
                                    ->content(fn ($record) => $record->success ? '✓ Erfolgreich' : '✗ Fehlgeschlagen'),
                                Placeholder::make('results_count')
                                    ->label('Anzahl Ergebnisse')
                                    ->content(fn ($record) => $record->results_count ?? '-'),
                            ]),
                    ]),

                Section::make('Aktivierte Filter')
                    ->schema([
                        Placeholder::make('filters')
                            ->label('')
                            ->content(function ($record) {
                                if (empty($record->filters) || !is_array($record->filters)) {
                                    return 'Keine Filter aktiv';
                                }
                                $formatted = [];
                                foreach ($record->filters as $key => $value) {
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
                                        $formatted[] = "✓ {$label}";
                                    }
                                }
                                return empty($formatted) ? 'Keine Filter aktiv' : implode('<br>', $formatted);
                            })
                            ->html(),
                    ]),

                Section::make('API Request')
                    ->schema([
                        Placeholder::make('request_body')
                            ->label('Request Body (JSON)')
                            ->content(fn ($record) => '<pre><code>' . json_encode($record->request_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</code></pre>')
                            ->html(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('API Response')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('response_status')
                                    ->label('HTTP Status Code')
                                    ->content(fn ($record) => $record->response_status ?? '-'),
                                Placeholder::make('error_message')
                                    ->label('Fehlermeldung')
                                    ->content(fn ($record) => $record->error_message ?? '-'),
                            ]),
                        Placeholder::make('response_data')
                            ->label('Response Data (JSON)')
                            ->content(fn ($record) => '<pre><code>' . json_encode($record->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</code></pre>')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
