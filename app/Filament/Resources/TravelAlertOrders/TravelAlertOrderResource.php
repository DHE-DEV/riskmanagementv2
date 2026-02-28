<?php

namespace App\Filament\Resources\TravelAlertOrders;

use App\Filament\Resources\TravelAlertOrders\Pages\ListTravelAlertOrders;
use App\Filament\Resources\TravelAlertOrders\Pages\ViewTravelAlertOrder;
use App\Filament\Resources\TravelAlertOrders\Tables\TravelAlertOrdersTable;
use App\Models\TravelAlertOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TravelAlertOrderResource extends Resource
{
    protected static ?string $model = TravelAlertOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'TravelAlert Bestellungen';

    protected static ?string $modelLabel = 'Bestellung';

    protected static ?string $pluralModelLabel = 'Bestellungen';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return TravelAlertOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTravelAlertOrders::route('/'),
            'view' => ViewTravelAlertOrder::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Verwaltung';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
}
