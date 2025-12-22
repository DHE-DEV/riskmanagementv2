<?php

namespace App\Filament\Resources\InfoSourceItems;

use App\Filament\Resources\InfoSourceItems\Pages\ListInfoSourceItems;
use App\Filament\Resources\InfoSourceItems\Pages\ViewInfoSourceItem;
use App\Filament\Resources\InfoSourceItems\Schemas\InfoSourceItemForm;
use App\Filament\Resources\InfoSourceItems\Tables\InfoSourceItemsTable;
use App\Models\InfoSourceItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InfoSourceItemResource extends Resource
{
    protected static ?string $model = InfoSourceItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Feed-Einträge';

    protected static ?string $modelLabel = 'Feed-Eintrag';

    protected static ?string $pluralModelLabel = 'Feed-Einträge';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return 'Daten-Import';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::new()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Schema $schema): Schema
    {
        return InfoSourceItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InfoSourceItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInfoSourceItems::route('/'),
            'view' => ViewInfoSourceItem::route('/{record}'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['infoSource']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'content'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Quelle' => $record->infoSource->name ?? 'Unbekannt',
            'Status' => $record->status_label,
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }
}
