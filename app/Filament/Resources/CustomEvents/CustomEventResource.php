<?php

namespace App\Filament\Resources\CustomEvents;

use App\Filament\Resources\CustomEvents\Pages\CreateCustomEvent;
use App\Filament\Resources\CustomEvents\Pages\EditCustomEvent;
use App\Filament\Resources\CustomEvents\Pages\ListCustomEvents;
use App\Filament\Resources\CustomEvents\Pages\ManageEventCountries;
use App\Filament\Resources\CustomEvents\Pages\ViewCustomEvent;
use App\Filament\Resources\CustomEvents\RelationManagers\CountriesRelationManager;
use App\Filament\Resources\CustomEvents\Schemas\CustomEventForm;
use App\Filament\Resources\CustomEvents\Tables\CustomEventsTable;
use App\Models\CustomEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomEventResource extends Resource
{
    protected static ?string $model = CustomEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Passolution Events';

    protected static ?string $modelLabel = 'Manuelles Event';

    protected static ?string $pluralModelLabel = 'Manuelle Events';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CustomEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CountriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomEvents::route('/'),
            'create' => CreateCustomEvent::route('/create'),
            'view' => ViewCustomEvent::route('/{record}'),
            'edit' => EditCustomEvent::route('/{record}/edit'),
            'manage-countries' => ManageEventCountries::route('/{record}/countries'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Zeige alle Datensätze an, auch archivierte und deaktivierte
        return parent::getEloquentQuery()
            ->withCount('clicks');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Event Management';
    }
}
