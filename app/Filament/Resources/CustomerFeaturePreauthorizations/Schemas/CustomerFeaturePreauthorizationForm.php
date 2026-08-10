<?php

namespace App\Filament\Resources\CustomerFeaturePreauthorizations\Schemas;

use App\Models\Customer;
use App\Models\CustomerFeatureOverride;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerFeaturePreauthorizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('pds_account_id')
                    ->label('PDS Account-ID')
                    ->helperText('Die Account-ID aus dem Login. Ein Kundenkonto muss dafuer noch nicht existieren.')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->columnSpan(1)
                    ->hint(fn ($state) => $state ? self::accountHint((int) $state) : null),

                Select::make('feature_key')
                    ->label('Feature')
                    ->options(CustomerFeatureOverride::getFeatureLabels())
                    ->default('navigation_risk_overview_enabled')
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->columnSpan(1),

                Toggle::make('enabled')
                    ->label('Freischalten')
                    ->helperText('Aus = Feature wird beim ersten Login gesperrt statt freigeschaltet.')
                    ->default(true)
                    ->columnSpan(1),

                TextInput::make('note')
                    ->label('Notiz')
                    ->placeholder('z.B. Herkunft der Liste')
                    ->maxLength(255)
                    ->columnSpan(1),
            ]);
    }

    /**
     * Zeigt direkt im Formular, ob es zu der Account-ID schon Kunden gibt -
     * sonst ist nicht erkennbar, ob die Vormerkung sofort oder erst beim
     * naechsten Login greift.
     */
    private static function accountHint(int $pdsAccountId): string
    {
        $count = Customer::query()->where('pds_account_id', $pdsAccountId)->count();

        return match (true) {
            $count === 0 => 'Noch kein Konto - greift beim ersten Login',
            $count === 1 => '1 Konto vorhanden',
            default => "{$count} Konten vorhanden",
        };
    }
}
