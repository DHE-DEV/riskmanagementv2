<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
