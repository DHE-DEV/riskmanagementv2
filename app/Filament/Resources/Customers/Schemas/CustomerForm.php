<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
            ->columns(2)
            ->components([
                // Linke Spalte
                Section::make('Allgemeine Informationen')
                    ->columnSpan(1)
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

                            TextInput::make('customer_type')
                                ->label('Kundentyp')
                                ->disabled(),

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
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('directory_listing_active')
                            ->label('Adressverzeichnis aktiv'),

                        Toggle::make('branch_management_active')
                            ->label('Filialen-Verwaltung aktiv'),

                        Toggle::make('hide_profile_completion')
                            ->label('Profil-Vervollständigung ausblenden'),
                    ]),

                Section::make('Passolution Integration')
                    ->columnSpan(1)
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
                    ->collapsible()
                    ->collapsed(),

                // Rechte Spalte
                Section::make('Firmeninformationen')
                    ->columnSpan(1)
                    ->columnStart(2)
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
                    ->columnSpan(1)
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
            ]);
    }
}
