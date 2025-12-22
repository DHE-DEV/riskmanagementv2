<?php

namespace App\Filament\Resources\PluginClientResource\Pages;

use App\Filament\Resources\PluginClientResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Components\RepeatableEntry;

class ViewPluginClient extends ViewRecord
{
    protected static string $resource = PluginClientResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kundendaten')
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('company_name')
                            ->label('Firma'),
                        TextEntry::make('contact_name')
                            ->label('Ansprechpartner'),
                        TextEntry::make('email')
                            ->label('E-Mail')
                            ->copyable(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'warning',
                                'suspended' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'active' => 'Aktiv',
                                'inactive' => 'Inaktiv',
                                'suspended' => 'Gesperrt',
                                default => $state,
                            }),
                    ]),

                Section::make('Adresse')
                    ->icon('heroicon-o-map-pin')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('customer.company_street')
                            ->label('Straße'),
                        TextEntry::make('customer.company_house_number')
                            ->label('Hausnummer'),
                        TextEntry::make('customer.company_postal_code')
                            ->label('PLZ'),
                        TextEntry::make('customer.company_city')
                            ->label('Ort'),
                        TextEntry::make('customer.company_country')
                            ->label('Land'),
                    ]),

                Section::make('API-Zugang')
                    ->icon('heroicon-o-key')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('activeKey.public_key')
                            ->label('Aktiver API-Key')
                            ->copyable()
                            ->copyMessage('API-Key kopiert!')
                            ->fontFamily('mono'),
                        TextEntry::make('activeKey.created_at')
                            ->label('Key erstellt am')
                            ->dateTime('d.m.Y H:i'),
                    ]),

                Section::make('Registrierte Domains')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        RepeatableEntry::make('domains')
                            ->label('')
                            ->schema([
                                TextEntry::make('domain')
                                    ->label('Domain')
                                    ->copyable(),
                                TextEntry::make('created_at')
                                    ->label('Hinzugefügt am')
                                    ->dateTime('d.m.Y H:i'),
                            ])
                            ->columns(2),
                    ]),

                Section::make('Statistik')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('usage_events_count')
                            ->label('Gesamtaufrufe')
                            ->state(fn ($record) => $record->usageEvents()->count())
                            ->badge()
                            ->color('success'),
                        TextEntry::make('usage_events_30days')
                            ->label('Aufrufe (30 Tage)')
                            ->state(fn ($record) => $record->usageEvents()->where('created_at', '>=', now()->subDays(30))->count())
                            ->badge()
                            ->color('info'),
                        TextEntry::make('usage_events_today')
                            ->label('Aufrufe (heute)')
                            ->state(fn ($record) => $record->usageEvents()->whereDate('created_at', today())->count())
                            ->badge()
                            ->color('primary'),
                    ]),

                Section::make('Zeitstempel')
                    ->icon('heroicon-o-clock')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Registriert am')
                            ->dateTime('d.m.Y H:i'),
                        TextEntry::make('updated_at')
                            ->label('Aktualisiert am')
                            ->dateTime('d.m.Y H:i'),
                    ]),
            ]);
    }
}
