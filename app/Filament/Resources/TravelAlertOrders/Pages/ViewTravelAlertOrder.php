<?php

namespace App\Filament\Resources\TravelAlertOrders\Pages;

use App\Filament\Resources\TravelAlertOrders\TravelAlertOrderResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewTravelAlertOrder extends ViewRecord
{
    protected static string $resource = TravelAlertOrderResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 2])
            ->components([
                // Linke Spalte
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Firmendaten')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Placeholder::make('company')
                                    ->label('Firmenname')
                                    ->content(fn ($record) => $record->company),
                                Placeholder::make('contact_name')
                                    ->label('Ansprechpartner')
                                    ->content(fn ($record) => trim(($record->first_name ?? '').' '.($record->last_name ?? '')) ?: '-'),
                                Placeholder::make('email')
                                    ->label('E-Mail')
                                    ->content(fn ($record) => $record->email),
                                Placeholder::make('phone')
                                    ->label('Telefon')
                                    ->content(fn ($record) => $record->phone),
                            ]),

                        Section::make('Adresse')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Placeholder::make('street')
                                    ->label('Straße')
                                    ->content(fn ($record) => $record->street),
                                Placeholder::make('postal_city')
                                    ->label('PLZ / Stadt')
                                    ->content(fn ($record) => $record->postal_code.' '.$record->city),
                                Placeholder::make('country')
                                    ->label('Land')
                                    ->content(fn ($record) => $record->country),
                            ]),
                    ]),

                // Rechte Spalte
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Abrechnung')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Placeholder::make('existing_billing')
                                    ->label('Bestehendes Abrechnungsverfahren')
                                    ->content(fn ($record) => $record->existing_billing === 'ja' ? 'Ja' : 'Nein'),
                            ]),

                        Section::make('Testversion')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Placeholder::make('trial_expires_at')
                                    ->label('Ablaufdatum Testversion')
                                    ->content(function ($record) {
                                        if (! $record->trial_expires_at) {
                                            return 'Nicht gesetzt';
                                        }
                                        $formatted = $record->trial_expires_at->format('d.m.Y');
                                        if ($record->trial_expires_at->isPast()) {
                                            return $formatted.' (abgelaufen)';
                                        }
                                        $days = now()->diffInDays($record->trial_expires_at);

                                        return $formatted.' (noch '.$days.' Tage)';
                                    }),
                            ]),

                        Section::make('Bemerkung')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Placeholder::make('remarks')
                                    ->label('')
                                    ->content(fn ($record) => $record->remarks ?: 'Keine Bemerkung'),
                            ]),

                        Section::make('Meta')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Placeholder::make('created_at')
                                    ->label('Eingegangen am')
                                    ->content(fn ($record) => $record->created_at->format('d.m.Y H:i:s')),
                                Placeholder::make('updated_at')
                                    ->label('Zuletzt aktualisiert')
                                    ->content(fn ($record) => $record->updated_at->format('d.m.Y H:i:s')),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setTrialExpiry')
                ->label('Testversion-Ablauf setzen')
                ->icon('heroicon-o-calendar')
                ->color('warning')
                ->form([
                    DatePicker::make('trial_expires_at')
                        ->label('Ablaufdatum Testversion')
                        ->default(fn () => $this->record->trial_expires_at)
                        ->native(false)
                        ->displayFormat('d.m.Y'),
                ])
                ->action(function (array $data) {
                    $this->record->update(['trial_expires_at' => $data['trial_expires_at']]);
                }),
            DeleteAction::make(),
        ];
    }
}
