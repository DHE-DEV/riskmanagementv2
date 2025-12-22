<?php

namespace App\Filament\Resources\InfoSources;

use App\Filament\Resources\InfoSources\Pages\CreateInfoSource;
use App\Filament\Resources\InfoSources\Pages\EditInfoSource;
use App\Filament\Resources\InfoSources\Pages\ListInfoSources;
use App\Filament\Resources\InfoSources\Schemas\InfoSourceForm;
use App\Filament\Resources\InfoSources\Tables\InfoSourcesTable;
use App\Models\InfoSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InfoSourceResource extends Resource
{
    protected static ?string $model = InfoSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRss;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Datenquellen';

    protected static ?string $modelLabel = 'Datenquelle';

    protected static ?string $pluralModelLabel = 'Datenquellen';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Daten-Import';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return InfoSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InfoSourcesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInfoSources::route('/'),
            'create' => CreateInfoSource::route('/create'),
            'edit' => EditInfoSource::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'description', 'url'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Typ' => $record->type_label,
            'Inhalt' => $record->content_type_label,
        ];
    }
}
