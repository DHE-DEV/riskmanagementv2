<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Allgemeine Informationen')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Name')
                                ->disabled(),

                            TextInput::make('email')
                                ->label('E-Mail')
                                ->email()
                                ->disabled(),

                            TextInput::make('customer_type')
                                ->label('Kundentyp')
                                ->disabled(),

                            TextInput::make('provider')
                                ->label('Login via')
                                ->disabled(),

                            DateTimePicker::make('email_verified_at')
                                ->label('E-Mail verifiziert am')
                                ->disabled(),

                            DateTimePicker::make('created_at')
                                ->label('Registriert am')
                                ->disabled(),
                        ]),
                    ]),

                Section::make('Firmeninformationen')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('company_name')
                                ->label('Firmenname')
                                ->disabled()
                                ->columnSpan(2),

                            TextInput::make('company_street')
                                ->label('Straße')
                                ->disabled(),

                            TextInput::make('company_house_number')
                                ->label('Hausnummer')
                                ->disabled(),

                            TextInput::make('company_postal_code')
                                ->label('PLZ')
                                ->disabled(),

                            TextInput::make('company_city')
                                ->label('Stadt')
                                ->disabled(),

                            TextInput::make('company_country')
                                ->label('Land')
                                ->disabled()
                                ->columnSpan(2),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Einstellungen')
                    ->schema([
                        Grid::make(3)->schema([
                            Toggle::make('directory_listing_active')
                                ->label('Adressverzeichnis aktiv')
                                ->disabled(),

                            Toggle::make('branch_management_active')
                                ->label('Filialen-Verwaltung aktiv')
                                ->disabled(),

                            Toggle::make('hide_profile_completion')
                                ->label('Profil-Vervollständigung ausblenden')
                                ->disabled(),
                        ]),
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
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
