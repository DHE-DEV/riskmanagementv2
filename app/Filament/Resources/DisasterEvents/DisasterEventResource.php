<?php

namespace App\Filament\Resources\DisasterEvents;

use App\Filament\Resources\DisasterEvents\Pages\CreateDisasterEvent;
use App\Filament\Resources\DisasterEvents\Pages\EditDisasterEvent;
use App\Filament\Resources\DisasterEvents\Pages\ListDisasterEvents;
use App\Filament\Resources\DisasterEvents\Schemas\DisasterEventForm;
use App\Filament\Resources\DisasterEvents\Tables\DisasterEventsTable;
use App\Models\DisasterEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DisasterEventResource extends Resource
{
    protected static ?string $model = DisasterEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'GDACS Events';

    protected static ?string $modelLabel = 'GDACS Event';

    protected static ?string $pluralModelLabel = 'GDACS Events';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return DisasterEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DisasterEventsTable::configure($table);
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
            'index' => ListDisasterEvents::route('/'),
            'create' => CreateDisasterEvent::route('/create'),
            'edit' => EditDisasterEvent::route('/{record}/edit'),
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
        return static::getModel()::active()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Event Management';
    }
}
