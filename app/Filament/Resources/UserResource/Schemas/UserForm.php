<?php

namespace App\Filament\Resources\UserResource\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Vollständiger Name'),

                TextInput::make('email')
                    ->label('E-Mail')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('benutzer@beispiel.de'),

                TextInput::make('password')
                    ->label('Passwort')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(8)
                    ->rules([Password::defaults()])
                    ->placeholder('Mindestens 8 Zeichen')
                    ->helperText(fn (string $context): string => $context === 'edit' ? 'Lassen Sie das Feld leer, um das Passwort nicht zu ändern.' : 'Klicken Sie auf "Passwort generieren" für einen sicheren Vorschlag')
                    ->suffixAction(
                        Action::make('generatePassword')
                            ->icon('heroicon-o-key')
                            ->tooltip('Sicheres Passwort generieren')
                            ->action(function ($set) {
                                $password = static::generateSecurePassword();
                                $set('password', $password);
                                $set('password_confirmation', $password);
                            })
                            ->visible(fn (string $context): bool => $context === 'create')
                    ),

                TextInput::make('password_confirmation')
                    ->label('Passwort bestätigen')
                    ->password()
                    ->dehydrated(false)
                    ->required(fn (string $context): bool => $context === 'create')
                    ->same('password')
                    ->placeholder('Passwort wiederholen')
                    ->visible(fn (string $context): bool => $context === 'create' || filled(request('data.password'))),

                Toggle::make('is_active')
                    ->label('Aktiv')
                    ->helperText('Deaktivierte Benutzer können sich nicht anmelden.')
                    ->default(true),

                Toggle::make('is_admin')
                    ->label('Administrator')
                    ->helperText('Administratoren haben vollen Zugriff auf das System.')
                    ->default(false),

                DateTimePicker::make('email_verified_at')
                    ->label('E-Mail verifiziert am')
                    ->helperText('Lassen Sie dieses Feld leer, um die E-Mail als nicht verifiziert zu markieren.')
                    ->nullable(),
            ]);
    }

    /**
     * Generate a secure password with mixed characters
     */
    private static function generateSecurePassword(): string
    {
        $length = 12;
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%&*+-=?';
        
        // Ensure at least one character from each set
        $password = '';
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Fill the rest randomly
        $allChars = $lowercase . $uppercase . $numbers . $symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password to avoid predictable patterns
        return str_shuffle($password);
    }
}
