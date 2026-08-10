<?php

namespace App\Filament\Resources\CustomerFeaturePreauthorizations;

use App\Filament\Resources\CustomerFeaturePreauthorizations\Pages\CreateCustomerFeaturePreauthorization;
use App\Filament\Resources\CustomerFeaturePreauthorizations\Pages\EditCustomerFeaturePreauthorization;
use App\Filament\Resources\CustomerFeaturePreauthorizations\Pages\ListCustomerFeaturePreauthorizations;
use App\Filament\Resources\CustomerFeaturePreauthorizations\Schemas\CustomerFeaturePreauthorizationForm;
use App\Filament\Resources\CustomerFeaturePreauthorizations\Tables\CustomerFeaturePreauthorizationsTable;
use App\Models\CustomerFeaturePreauthorization;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema as DatabaseSchema;

/**
 * Vorgemerkte Feature-Freischaltungen fuer pds_account_ids.
 *
 * Gedacht fuer Accounts, die es auf der Plattform gibt, aber noch nie hier
 * eingeloggt waren - fuer die also kein Kundendatensatz existiert, an dem man
 * das Feature setzen koennte. Beim ersten Login wird die Vormerkung automatisch
 * in ein Feature-Override uebersetzt.
 */
class CustomerFeaturePreauthorizationResource extends Resource
{
    protected static ?string $model = CustomerFeaturePreauthorization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $recordTitleAttribute = 'pds_account_id';

    protected static ?string $navigationLabel = 'Feature-Vormerkungen';

    protected static ?string $modelLabel = 'Feature-Vormerkung';

    protected static ?string $pluralModelLabel = 'Feature-Vormerkungen';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return CustomerFeaturePreauthorizationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerFeaturePreauthorizationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerFeaturePreauthorizations::route('/'),
            'create' => CreateCustomerFeaturePreauthorization::route('/create'),
            'edit' => EditCustomerFeaturePreauthorization::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Benutzerverwaltung';
    }

    /**
     * Zeigt, wie viele Vormerkungen noch auf ihren ersten Login warten.
     *
     * Der Tabellen-Check faengt den Zustand zwischen Deploy und Migration ab -
     * ohne ihn wuerde eine fehlende Tabelle das gesamte Panel mit einem
     * SQL-Fehler lahmlegen, nicht nur diesen Menuepunkt.
     */
    public static function getNavigationBadge(): ?string
    {
        if (! DatabaseSchema::hasTable('customer_feature_preauthorizations')) {
            return null;
        }

        $open = static::getModel()::query()->whereNull('applied_at')->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Noch nicht eingeloeste Vormerkungen';
    }
}
