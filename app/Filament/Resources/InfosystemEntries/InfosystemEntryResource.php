<?php

namespace App\Filament\Resources\InfosystemEntries;

use App\Filament\Resources\InfosystemEntries\Pages\CreateInfosystemEntry;
use App\Filament\Resources\InfosystemEntries\Pages\EditInfosystemEntry;
use App\Filament\Resources\InfosystemEntries\Pages\ListInfosystemEntries;
use App\Filament\Resources\InfosystemEntries\Schemas\InfosystemEntryForm;
use App\Filament\Resources\InfosystemEntries\Tables\InfosystemEntriesTable;
use App\Models\InfosystemEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InfosystemEntryResource extends Resource
{
    protected static ?string $model = InfosystemEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return InfosystemEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InfosystemEntriesTable::configure($table);
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
            'index' => ListInfosystemEntries::route('/'),
            'create' => CreateInfosystemEntry::route('/create'),
            'edit' => EditInfosystemEntry::route('/{record}/edit'),
        ];
    }
}
