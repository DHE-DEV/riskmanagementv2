<?php

namespace App\Filament\Resources\Continents;

use App\Filament\Resources\Continents\Pages\CreateContinent;
use App\Filament\Resources\Continents\Pages\EditContinent;
use App\Filament\Resources\Continents\Pages\ListContinents;
use App\Filament\Resources\Continents\Schemas\ContinentForm;
use App\Filament\Resources\Continents\Tables\ContinentsTable;
use App\Models\Continent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContinentResource extends Resource
{
    protected static ?string $model = Continent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?string $navigationLabel = 'Kontinente';

    protected static ?string $modelLabel = 'Kontinent';

    protected static ?string $pluralModelLabel = 'Kontinente';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ContinentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContinentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContinents::route('/'),
            'create' => CreateContinent::route('/create'),
            'edit' => EditContinent::route('/{record}/edit'),
        ];
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
        return 'Geografische Daten';
    }
}
