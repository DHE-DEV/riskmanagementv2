<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PluginClientResource\Pages;
use App\Filament\Resources\PluginClientResource\RelationManagers;
use App\Filament\Resources\PluginClientResource\Tables\PluginClientsTable;
use App\Models\PluginClient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
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
            'view' => Pages\ViewPluginClient::route('/{record}'),
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
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
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
