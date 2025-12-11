<?php

namespace App\Filament\Resources\AirportCodes;

use App\Filament\Resources\AirportCodes\Pages\EditAirportCode;
use App\Filament\Resources\AirportCodes\Pages\ListAirportCodes;
use App\Filament\Resources\AirportCodes\Pages\ViewAirportCode;
use App\Filament\Resources\AirportCodes\Schemas\AirportCodeForm;
use App\Filament\Resources\AirportCodes\Tables\AirportCodesTable;
use App\Models\AirportCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AirportCodeResource extends Resource
{
    protected static ?string $model = AirportCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Flughafen Codes';

    protected static ?string $modelLabel = 'Flughafen Code';

    protected static ?string $pluralModelLabel = 'Flughafen Codes';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return AirportCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AirportCodesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AirlinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAirportCodes::route('/'),
            'view' => ViewAirportCode::route('/{record}'),
            'edit' => EditAirportCode::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return number_format(static::getModel()::count(), 0, ',', '.');
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Geografische Daten';
    }
}
