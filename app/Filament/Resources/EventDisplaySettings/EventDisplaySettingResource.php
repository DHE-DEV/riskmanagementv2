<?php

namespace App\Filament\Resources\EventDisplaySettings;

use App\Filament\Resources\EventDisplaySettings\Pages\EditEventDisplaySetting;
use App\Filament\Resources\EventDisplaySettings\Schemas\EventDisplaySettingForm;
use App\Models\EventDisplaySetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class EventDisplaySettingResource extends Resource
{
    protected static ?string $model = EventDisplaySetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;

    protected static ?string $navigationLabel = 'Event-Anzeige';

    protected static ?string $modelLabel = 'Event-Anzeige Einstellungen';

    protected static ?string $pluralModelLabel = 'Event-Anzeige Einstellungen';

    protected static ?int $navigationSort = 99;

    public static function getNavigationGroup(): ?string
    {
        return 'Verwaltung';
    }

    public static function form(Schema $schema): Schema
    {
        return EventDisplaySettingForm::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => EditEventDisplaySetting::route('/'),
        ];
    }

    // Singleton: Always edit the first record
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
