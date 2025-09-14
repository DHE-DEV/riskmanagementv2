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
use Filament\Tables\Table;

class InfosystemEntryResource extends Resource
{
    protected static ?string $model = InfosystemEntry::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Passolution Infosystem';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'Infosystem Eintrag';

    protected static ?string $pluralModelLabel = 'Infosystem Einträge';

    protected static ?string $recordTitleAttribute = 'header';

    public static function getNavigationGroup(): ?string
    {
        return 'API Schnittstellen';
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
            // Create and Edit disabled - data comes from API only
            // 'create' => CreateInfosystemEntry::route('/create'),
            // 'edit' => EditInfosystemEntry::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Disable create - data comes from API
    }
}
