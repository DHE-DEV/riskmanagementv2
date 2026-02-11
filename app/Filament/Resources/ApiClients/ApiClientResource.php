<?php

namespace App\Filament\Resources\ApiClients;

use App\Filament\Resources\ApiClients\Pages\CreateApiClient;
use App\Filament\Resources\ApiClients\Pages\EditApiClient;
use App\Filament\Resources\ApiClients\Pages\ListApiClients;
use App\Filament\Resources\ApiClients\Pages\ViewApiClient;
use App\Filament\Resources\ApiClients\RelationManagers\CustomEventsRelationManager;
use App\Filament\Resources\ApiClients\Schemas\ApiClientForm;
use App\Filament\Resources\ApiClients\Tables\ApiClientsTable;
use App\Models\ApiClient;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApiClientResource extends Resource
{
    protected static ?string $model = ApiClient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;

    protected static ?string $navigationLabel = 'API-Kunden';

    protected static ?string $modelLabel = 'API-Kunde';

    protected static ?string $pluralModelLabel = 'API-Kunden';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return ApiClientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiClientsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 2])
            ->components([
                // Left column
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Kundendaten')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name'),
                                TextEntry::make('company_name')
                                    ->label('Firma'),
                                TextEntry::make('contact_email')
                                    ->label('E-Mail'),
                                TextEntry::make('description')
                                    ->label('Beschreibung')
                                    ->placeholder('Keine Beschreibung'),
                            ]),
                        Section::make('Logo')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                ImageEntry::make('logo_path')
                                    ->label('')
                                    ->disk('public')
                                    ->height(80)
                                    ->defaultImageUrl(url('/Passolution-Logo-klein.png')),
                            ]),
                    ]),
                // Right column
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Einstellungen')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'inactive' => 'gray',
                                        'suspended' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'active' => 'Aktiv',
                                        'inactive' => 'Inaktiv',
                                        'suspended' => 'Gesperrt',
                                        default => $state,
                                    }),
                                IconEntry::make('auto_approve_events')
                                    ->label('Auto-Freigabe')
                                    ->boolean(),
                                TextEntry::make('rate_limit')
                                    ->label('Rate Limit')
                                    ->suffix(' Req/Min'),
                            ]),
                        Section::make('Statistiken')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                TextEntry::make('custom_events_count')
                                    ->label('Anzahl Events')
                                    ->state(fn (ApiClient $record): int => $record->customEvents()->count())
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('request_logs_count')
                                    ->label('API-Requests (30 Tage)')
                                    ->state(fn (ApiClient $record): int => $record->requestLogs()
                                        ->where('created_at', '>=', now()->subDays(30))
                                        ->count())
                                    ->badge()
                                    ->color('info'),
                            ]),
                        Section::make('Zeitstempel')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Erstellt')
                                    ->dateTime('d.m.Y H:i'),
                                TextEntry::make('updated_at')
                                    ->label('Aktualisiert')
                                    ->dateTime('d.m.Y H:i'),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CustomEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiClients::route('/'),
            'create' => CreateApiClient::route('/create'),
            'view' => ViewApiClient::route('/{record}'),
            'edit' => EditApiClient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('customEvents');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::active()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'API Schnittstellen';
    }
}
