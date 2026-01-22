<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PluginClientResource\Pages;
use App\Filament\Resources\PluginClientResource\RelationManagers;
use App\Filament\Resources\PluginClientResource\Tables\PluginClientsTable;
use App\Models\Customer;
use App\Models\PluginClient;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PluginClientResource extends Resource
{
    protected static ?string $model = PluginClient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static ?string $navigationLabel = 'Plugin-Kunden';

    protected static ?string $modelLabel = 'Plugin-Kunde';

    protected static ?string $pluralModelLabel = 'Plugin-Kunden';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kundendaten')
                    ->icon('heroicon-o-building-office')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Verknüpfter Kunde (optional)')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Kein Kunde verknüpft'),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('company_name')
                                    ->label('Firma')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('contact_name')
                                    ->label('Ansprechpartner')
                                    ->maxLength(255),
                            ]),
                        TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Aktiv',
                                'inactive' => 'Inaktiv',
                                'suspended' => 'Gesperrt',
                            ])
                            ->default('active')
                            ->required(),
                        Toggle::make('allow_app_access')
                            ->label('App-Zugang erlaubt')
                            ->helperText('Ermöglicht Nutzung ohne Domain-Validierung (für WebView-Apps)')
                            ->default(false),
                    ]),
                Section::make('Adresse')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('street')
                                    ->label('Straße')
                                    ->maxLength(255),
                                TextInput::make('house_number')
                                    ->label('Hausnummer')
                                    ->maxLength(20),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('postal_code')
                                    ->label('PLZ')
                                    ->maxLength(20),
                                TextInput::make('city')
                                    ->label('Ort')
                                    ->maxLength(255),
                                TextInput::make('country')
                                    ->label('Land')
                                    ->maxLength(255),
                            ]),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->extraAttributes(['class' => 'items-start'])
                    ->schema([
                        // Linke Spalte
                        Grid::make(1)
                            ->columnSpan(1)
                            ->extraAttributes(['class' => 'flex flex-col gap-6'])
                            ->schema([
                                Section::make('Kundendaten')
                                    ->icon('heroicon-o-building-office')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('company_name')
                                                    ->label('Firma'),
                                                TextEntry::make('contact_name')
                                                    ->label('Ansprechpartner'),
                                                TextEntry::make('email')
                                                    ->label('E-Mail')
                                                    ->copyable(),
                                                TextEntry::make('status')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'active' => 'success',
                                                        'inactive' => 'warning',
                                                        'suspended' => 'danger',
                                                        default => 'gray',
                                                    })
                                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                                        'active' => 'Aktiv',
                                                        'inactive' => 'Inaktiv',
                                                        'suspended' => 'Gesperrt',
                                                        default => $state,
                                                    }),
                                                IconEntry::make('allow_app_access')
                                                    ->label('App-Zugang')
                                                    ->boolean()
                                                    ->trueIcon('heroicon-o-device-phone-mobile')
                                                    ->falseIcon('heroicon-o-x-mark')
                                                    ->trueColor('success')
                                                    ->falseColor('gray'),
                                            ]),
                                    ]),
                                Section::make('Verknüpfter Kunde')
                                    ->icon('heroicon-o-user')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('customer.name')
                                                    ->label('Kunde')
                                                    ->url(fn ($record) => $record->customer
                                                        ? route('filament.admin.resources.customers.edit', ['record' => $record->customer_id])
                                                        : null)
                                                    ->openUrlInNewTab()
                                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                                    ->iconPosition('after')
                                                    ->placeholder('Kein Kunde verknüpft'),
                                                TextEntry::make('customer.email')
                                                    ->label('Kunden-E-Mail')
                                                    ->copyable()
                                                    ->placeholder('-'),
                                                TextEntry::make('customer.customer_type')
                                                    ->label('Kundentyp')
                                                    ->badge()
                                                    ->color(fn (?string $state): string => match ($state) {
                                                        'business' => 'success',
                                                        'private' => 'info',
                                                        default => 'gray',
                                                    })
                                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                        'business' => 'Firmenkunde',
                                                        'private' => 'Privatkunde',
                                                        default => '-',
                                                    }),
                                                TextEntry::make('customer.business_type')
                                                    ->label('Geschäftstyp')
                                                    ->badge()
                                                    ->color('primary')
                                                    ->formatStateUsing(function ($state): string {
                                                        if (empty($state)) {
                                                            return '-';
                                                        }
                                                        $labels = [
                                                            'travel_agency' => 'Reisebüro',
                                                            'organizer' => 'Veranstalter',
                                                            'online_provider' => 'Online Anbieter',
                                                            'mobile_travel_consultant' => 'Mobiler Reiseberater',
                                                            'software_provider' => 'Softwareanbieter',
                                                            'other' => 'Sonstiges',
                                                        ];
                                                        if (is_array($state)) {
                                                            return implode(', ', array_map(fn ($t) => $labels[$t] ?? $t, $state));
                                                        }
                                                        return $labels[$state] ?? $state;
                                                    }),
                                            ]),
                                    ])
                                    ->visible(fn ($record) => $record->customer !== null),
                                Section::make('Adresse')
                                    ->icon('heroicon-o-map-pin')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('street')
                                                    ->label('Straße'),
                                                TextEntry::make('house_number')
                                                    ->label('Hausnummer'),
                                                TextEntry::make('postal_code')
                                                    ->label('PLZ'),
                                                TextEntry::make('city')
                                                    ->label('Ort'),
                                                TextEntry::make('country')
                                                    ->label('Land'),
                                            ]),
                                    ]),
                            ]),

                        // Rechte Spalte
                        Grid::make(1)
                            ->columnSpan(1)
                            ->extraAttributes(['class' => 'flex flex-col gap-6'])
                            ->schema([
                                Section::make('Statistik')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        TextEntry::make('usage_events_count')
                                            ->label('Gesamtaufrufe')
                                            ->getStateUsing(fn ($record) => $record->usageEvents()->count())
                                            ->badge()
                                            ->color('success'),
                                        TextEntry::make('usage_events_30days')
                                            ->label('Aufrufe (30 Tage)')
                                            ->getStateUsing(fn ($record) => $record->usageEvents()->where('created_at', '>=', now()->subDays(30))->count())
                                            ->badge()
                                            ->color('info'),
                                        TextEntry::make('usage_events_today')
                                            ->label('Aufrufe (heute)')
                                            ->getStateUsing(fn ($record) => $record->usageEvents()->whereDate('created_at', today())->count())
                                            ->badge()
                                            ->color('primary'),
                                    ]),
                                Section::make('API-Zugang')
                                    ->icon('heroicon-o-key')
                                    ->schema([
                                        TextEntry::make('activeKey.public_key')
                                            ->label('Aktiver API-Key')
                                            ->copyable()
                                            ->copyMessage('API-Key kopiert!')
                                            ->fontFamily('mono'),
                                        TextEntry::make('activeKey.created_at')
                                            ->label('Key erstellt am')
                                            ->dateTime('d.m.Y H:i'),
                                        TextEntry::make('integration_url_events')
                                            ->label('Ereignisliste')
                                            ->getStateUsing(fn ($record) => $record->activeKey
                                                ? 'https://global-travel-monitor.eu/embed/events?key=' . $record->activeKey->public_key
                                                : null)
                                            ->copyable()
                                            ->copyMessage('Link kopiert!')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-o-clipboard-document')
                                            ->iconPosition('after')
                                            ->placeholder('Kein API-Key vorhanden'),
                                        TextEntry::make('integration_url_map')
                                            ->label('Kartenansicht')
                                            ->getStateUsing(fn ($record) => $record->activeKey
                                                ? 'https://global-travel-monitor.eu/embed/map?key=' . $record->activeKey->public_key
                                                : null)
                                            ->copyable()
                                            ->copyMessage('Link kopiert!')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-o-clipboard-document')
                                            ->iconPosition('after')
                                            ->placeholder('Kein API-Key vorhanden'),
                                        TextEntry::make('integration_url_dashboard')
                                            ->label('Komplettansicht')
                                            ->getStateUsing(fn ($record) => $record->activeKey
                                                ? 'https://global-travel-monitor.eu/embed/dashboard?key=' . $record->activeKey->public_key
                                                : null)
                                            ->copyable()
                                            ->copyMessage('Link kopiert!')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-o-clipboard-document')
                                            ->iconPosition('after')
                                            ->placeholder('Kein API-Key vorhanden'),
                                    ]),
                                Section::make('Zeitstempel')
                                    ->icon('heroicon-o-clock')
                                    ->collapsed()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('created_at')
                                                    ->label('Registriert am')
                                                    ->dateTime('d.m.Y H:i'),
                                                TextEntry::make('updated_at')
                                                    ->label('Aktualisiert am')
                                                    ->dateTime('d.m.Y H:i'),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return PluginClientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DomainsRelationManager::class,
            RelationManagers\UsageEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPluginClients::route('/'),
            'create' => Pages\CreatePluginClient::route('/create'),
            'view' => Pages\ViewPluginClient::route('/{record}'),
            'edit' => Pages\EditPluginClient::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'activeKey', 'domains'])
            ->withCount(['domains', 'usageEvents']);
    }

    public static function canCreate(): bool
    {
        return auth()->user() && auth()->user()->isAdmin() && auth()->user()->isActive();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user() && auth()->user()->isAdmin() && auth()->user()->isActive();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user() && auth()->user()->isAdmin() && auth()->user()->isActive();
    }

    public static function canViewAny(): bool
    {
        return auth()->user() && auth()->user()->isAdmin() && auth()->user()->isActive();
    }

    public static function canView(Model $record): bool
    {
        return auth()->user() && auth()->user()->isAdmin() && auth()->user()->isActive();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Plugin';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
