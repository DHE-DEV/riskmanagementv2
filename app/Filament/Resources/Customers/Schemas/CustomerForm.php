<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'lg' => 2,
                ])
                ->columnSpanFull()
                ->schema([
                    // Linke Spalte - Container
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Section::make('Allgemeine Informationen')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('name')
                                            ->label('Name')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('email')
                                            ->label('E-Mail')
                                            ->email()
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignorable: fn ($record) => $record),

                                        Select::make('customer_type')
                                            ->label('Kundentyp')
                                            ->options([
                                                'private' => 'Privatkunde',
                                                'business' => 'Firmenkunde',
                                            ]),

                                        CheckboxList::make('business_type')
                                            ->label('Geschäftstyp')
                                            ->options([
                                                'travel_agency' => 'Reisebüro',
                                                'organizer' => 'Veranstalter',
                                                'online_provider' => 'Online Anbieter',
                                                'mobile_travel_consultant' => 'Mobiler Reiseberater',
                                                'software_provider' => 'Softwareanbieter',
                                                'other' => 'Sonstiges',
                                            ])
                                            ->columns(2),

                                        TextInput::make('provider')
                                            ->label('Login via')
                                            ->disabled(),

                                        DateTimePicker::make('email_verified_at')
                                            ->label('E-Mail verifiziert am'),

                                        DateTimePicker::make('created_at')
                                            ->label('Registriert am')
                                            ->disabled(),
                                    ]),
                                ]),

                            Section::make('Einstellungen')
                                ->schema([
                                    Toggle::make('directory_listing_active')
                                        ->label('Adressverzeichnis aktiv'),

                                    Toggle::make('branch_management_active')
                                        ->label('Filialen-Verwaltung aktiv'),

                                    Toggle::make('hide_profile_completion')
                                        ->label('Profil-Vervollständigung ausblenden'),
                                ]),

                            Section::make('GTM API Einstellungen')
                                ->schema([
                                    Toggle::make('gtm_api_enabled')
                                        ->label('GTM API Zugang aktiv')
                                        ->helperText('Aktiviert den Zugang zur Global Travel Monitor JSON API. Der Kunde muss seinen API-Token neu generieren.'),

                                    TextInput::make('gtm_api_rate_limit')
                                        ->label('Rate Limit (Anfragen/Minute)')
                                        ->numeric()
                                        ->default(60)
                                        ->minValue(1)
                                        ->maxValue(1000)
                                        ->helperText('Maximale API-Anfragen pro Minute für diesen Kunden'),
                                ])
                                ->collapsible(),

                            Section::make('Feature-Überschreibungen')
                                ->description('Überschreiben Sie die globalen .env-Einstellungen für diesen Kunden. Leere Felder verwenden die Standard-Einstellung.')
                                ->relationship('featureOverrides')
                                ->schema([
                                    Placeholder::make('info')
                                        ->content('Aktiviert = Feature für diesen Kunden einblenden, auch wenn global deaktiviert. Deaktiviert = Feature ausblenden, auch wenn global aktiviert. Nicht gesetzt = Globale Einstellung verwenden.')
                                        ->columnSpanFull(),

                                    Grid::make(2)->schema([
                                        Select::make('navigation_events_enabled')
                                            ->label('Ereignisse')
                                            ->options([
                                                '1' => 'Aktiviert',
                                                '0' => 'Deaktiviert',
                                            ])
                                            ->placeholder('Standard (.env)')
                                            ->native(false),

                                        Select::make('navigation_entry_conditions_enabled')
                                            ->label('Einreisebestimmungen')
                                            ->options([
                                                '1' => 'Aktiviert',
                                                '0' => 'Deaktiviert',
                                            ])
                                            ->placeholder('Standard (.env)')
                                            ->native(false),

                                        Select::make('navigation_booking_enabled')
                                            ->label('Buchung')
                                            ->options([
                                                '1' => 'Aktiviert',
                                                '0' => 'Deaktiviert',
                                            ])
                                            ->placeholder('Standard (.env)')
                                            ->native(false),

                                        Select::make('navigation_airports_enabled')
                                            ->label('Flughäfen')
                                            ->options([
                                                '1' => 'Aktiviert',
                                                '0' => 'Deaktiviert',
                                            ])
                                            ->placeholder('Standard (.env)')
                                            ->native(false),

                                        Select::make('navigation_branches_enabled')
                                            ->label('Filialen')
                                            ->options([
                                                '1' => 'Aktiviert',
                                                '0' => 'Deaktiviert',
                                            ])
                                            ->placeholder('Standard (.env)')
                                            ->native(false),

                                        Select::make('navigation_my_travelers_enabled')
                                            ->label('Meine Reisenden')
                                            ->options([
                                                '1' => 'Aktiviert',
                                                '0' => 'Deaktiviert',
                                            ])
                                            ->placeholder('Standard (.env)')
                                            ->native(false),

                                        Select::make('navigation_risk_overview_enabled')
                                            ->label('Risiko-Übersicht')
                                            ->options([
                                                '1' => 'Aktiviert',
                                                '0' => 'Deaktiviert',
                                            ])
                                            ->placeholder('Standard (.env)')
                                            ->native(false),

                                        Select::make('navigation_cruise_enabled')
                                            ->label('Kreuzfahrten')
                                            ->options([
                                                '1' => 'Aktiviert',
                                                '0' => 'Deaktiviert',
                                            ])
                                            ->placeholder('Standard (.env)')
                                            ->native(false),

                                        Select::make('navigation_business_visa_enabled')
                                            ->label('Business Visum')
                                            ->options([
                                                '1' => 'Aktiviert',
                                                '0' => 'Deaktiviert',
                                            ])
                                            ->placeholder('Standard (.env)')
                                            ->native(false),

                                        Select::make('navigation_center_map_enabled')
                                            ->label('Karte zentrieren')
                                            ->options([
                                                '1' => 'Aktiviert',
                                                '0' => 'Deaktiviert',
                                            ])
                                            ->placeholder('Standard (.env)')
                                            ->native(false),

                                        Select::make('navigation_visumpoint_enabled')
                                            ->label('VisumPoint')
                                            ->options([
                                                '1' => 'Aktiviert',
                                                '0' => 'Deaktiviert',
                                            ])
                                            ->placeholder('Standard (.env)')
                                            ->native(false),
                                    ]),
                                ])
                                ->collapsible()
                                ->collapsed(),

                            Section::make('Passolution Integration')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('passolution_subscription_type')
                                            ->label('Abo-Typ')
                                            ->disabled(),

                                        DateTimePicker::make('passolution_subscription_updated_at')
                                            ->label('Abo aktualisiert am')
                                            ->disabled(),

                                        DateTimePicker::make('passolution_token_expires_at')
                                            ->label('Token läuft ab')
                                            ->disabled(),

                                        DateTimePicker::make('passolution_refresh_token_expires_at')
                                            ->label('Refresh Token läuft ab')
                                            ->disabled(),
                                    ]),
                                ])
                                ->collapsible(),
                        ]),

                    // Rechte Spalte - Container
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Section::make('Firmeninformationen')
                                ->schema([
                                    TextInput::make('company_name')
                                        ->label('Firmenname')
                                        ->maxLength(255),

                                    TextInput::make('company_additional')
                                        ->label('Zusatz')
                                        ->maxLength(255),

                                    Grid::make(2)->schema([
                                        TextInput::make('company_street')
                                            ->label('Straße')
                                            ->maxLength(255),

                                        TextInput::make('company_house_number')
                                            ->label('Hausnummer')
                                            ->maxLength(20),

                                        TextInput::make('company_postal_code')
                                            ->label('PLZ')
                                            ->maxLength(20),

                                        TextInput::make('company_city')
                                            ->label('Stadt')
                                            ->maxLength(255),
                                    ]),

                                    TextInput::make('company_country')
                                        ->label('Land')
                                        ->maxLength(255),
                                ]),

                            Section::make('Rechnungsadresse')
                                ->schema([
                                    TextInput::make('billing_company_name')
                                        ->label('Firmenname')
                                        ->maxLength(255),

                                    TextInput::make('billing_additional')
                                        ->label('Zusatz')
                                        ->maxLength(255),

                                    Grid::make(2)->schema([
                                        TextInput::make('billing_street')
                                            ->label('Straße')
                                            ->maxLength(255),

                                        TextInput::make('billing_house_number')
                                            ->label('Hausnummer')
                                            ->maxLength(20),

                                        TextInput::make('billing_postal_code')
                                            ->label('PLZ')
                                            ->maxLength(20),

                                        TextInput::make('billing_city')
                                            ->label('Stadt')
                                            ->maxLength(255),
                                    ]),

                                    TextInput::make('billing_country')
                                        ->label('Land')
                                        ->maxLength(255),
                                ]),
                        ]),
                ]),
            ]);
    }
}
