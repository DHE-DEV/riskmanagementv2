<?php

namespace App\Filament\Resources\ApiClients\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApiClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kundeninformationen')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('company_name')
                                    ->label('Firma')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        TextInput::make('contact_email')
                            ->label('E-Mail')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
                Section::make('Logo')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->disk('public')
                            ->directory('api-client-logos')
                            ->image()
                            ->imageEditor()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                            ->helperText('Max. 2MB. PNG, JPG oder SVG.'),
                    ]),
                Section::make('Einstellungen')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Aktiv',
                                'inactive' => 'Inaktiv',
                                'suspended' => 'Gesperrt',
                            ])
                            ->default('active')
                            ->required(),
                        Toggle::make('auto_approve_events')
                            ->label('Events automatisch freigeben')
                            ->helperText('Wenn aktiviert, werden Events dieses Kunden sofort veröffentlicht ohne Review.')
                            ->default(false),
                        TextInput::make('rate_limit')
                            ->label('Rate Limit (Requests/Minute)')
                            ->numeric()
                            ->default(60)
                            ->minValue(1)
                            ->maxValue(1000)
                            ->required(),
                    ]),
            ]);
    }
}
