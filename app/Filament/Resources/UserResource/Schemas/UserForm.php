<?php

namespace App\Filament\Resources\UserResource\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Benutzerinformationen')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Vollständiger Name'),

                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('benutzer@beispiel.de'),

                        Forms\Components\TextInput::make('password')
                            ->label('Passwort')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->rules([Password::defaults()])
                            ->placeholder('Mindestens 8 Zeichen')
                            ->helperText(fn (string $context): string => $context === 'edit' ? 'Lassen Sie das Feld leer, um das Passwort nicht zu ändern.' : ''),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Passwort bestätigen')
                            ->password()
                            ->dehydrated(false)
                            ->required(fn (string $context): bool => $context === 'create')
                            ->same('password')
                            ->placeholder('Passwort wiederholen')
                            ->visible(fn (string $context): bool => $context === 'create' || filled(request('data.password'))),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Berechtigungen')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->helperText('Deaktivierte Benutzer können sich nicht anmelden.')
                            ->default(true),

                        Forms\Components\Toggle::make('is_admin')
                            ->label('Administrator')
                            ->helperText('Administratoren haben vollen Zugriff auf das System.')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('E-Mail-Verifizierung')
                    ->schema([
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('E-Mail verifiziert am')
                            ->helperText('Lassen Sie dieses Feld leer, um die E-Mail als nicht verifiziert zu markieren.')
                            ->nullable(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
