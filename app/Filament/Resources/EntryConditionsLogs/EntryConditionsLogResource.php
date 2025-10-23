<?php

namespace App\Filament\Resources\EntryConditionsLogs;

use App\Filament\Resources\EntryConditionsLogs\Pages\ListEntryConditionsLogs;
use App\Filament\Resources\EntryConditionsLogs\Pages\ViewEntryConditionsLog;
use App\Filament\Resources\EntryConditionsLogs\Tables\EntryConditionsLogsTable;
use App\Models\EntryConditionsLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EntryConditionsLogResource extends Resource
{
    protected static ?string $model = EntryConditionsLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Einreisebestimmungen Logs';

    protected static ?string $modelLabel = 'Einreisebestimmungen Log';

    protected static ?string $pluralModelLabel = 'Einreisebestimmungen Logs';

    public static function table(Table $table): Table
    {
        return EntryConditionsLogsTable::configure($table);
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
            'index' => ListEntryConditionsLogs::route('/'),
            'view' => ViewEntryConditionsLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
