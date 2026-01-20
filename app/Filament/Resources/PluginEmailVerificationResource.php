<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PluginEmailVerificationResource\Pages;
use App\Models\PluginEmailVerification;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PluginEmailVerificationResource extends Resource
{
    protected static ?string $model = PluginEmailVerification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Ausstehende Registrierungen';

    protected static ?string $modelLabel = 'Ausstehende Registrierung';

    protected static ?string $pluralModelLabel = 'Ausstehende Registrierungen';

    protected static ?int $navigationSort = 11;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Registrierungsdaten')
                    ->icon('heroicon-o-user-plus')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('email')
                                    ->label('E-Mail')
                                    ->copyable(),
                                TextEntry::make('status_label')
                                    ->label('Status')
                                    ->badge()
                                    ->getStateUsing(fn ($record) => self::getStatusLabel($record))
                                    ->color(fn ($record) => self::getStatusColor($record)),
                                TextEntry::make('form_data.company_name')
                                    ->label('Firma'),
                                TextEntry::make('form_data.contact_name')
                                    ->label('Ansprechpartner'),
                                TextEntry::make('form_data.domain')
                                    ->label('Domain'),
                                TextEntry::make('attempts')
                                    ->label('Fehlversuche')
                                    ->badge()
                                    ->color(fn ($state) => $state >= 3 ? 'danger' : ($state > 0 ? 'warning' : 'success')),
                            ]),
                    ]),

                Section::make('Adresse')
                    ->icon('heroicon-o-map-pin')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('form_data.company_street')
                                    ->label('Straße'),
                                TextEntry::make('form_data.company_house_number')
                                    ->label('Hausnummer'),
                                TextEntry::make('form_data.company_postal_code')
                                    ->label('PLZ'),
                                TextEntry::make('form_data.company_city')
                                    ->label('Ort'),
                                TextEntry::make('form_data.company_country')
                                    ->label('Land'),
                            ]),
                    ]),

                Section::make('Zeitstempel')
                    ->icon('heroicon-o-clock')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Registrierung gestartet')
                                    ->dateTime('d.m.Y H:i'),
                                TextEntry::make('expires_at')
                                    ->label('Code gültig bis')
                                    ->dateTime('d.m.Y H:i'),
                                TextEntry::make('verified_at')
                                    ->label('Verifiziert am')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch nicht verifiziert'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('E-Mail kopiert!'),

                TextColumn::make('form_data.company_name')
                    ->label('Firma')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('form_data', 'like', "%{$search}%");
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("JSON_EXTRACT(form_data, '$.company_name') {$direction}");
                    }),

                TextColumn::make('form_data.contact_name')
                    ->label('Ansprechpartner')
                    ->toggleable(),

                TextColumn::make('form_data.domain')
                    ->label('Domain')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => self::getStatusLabel($record))
                    ->color(fn ($record) => self::getStatusColor($record)),

                TextColumn::make('attempts')
                    ->label('Versuche')
                    ->badge()
                    ->color(fn ($state) => $state >= 3 ? 'danger' : ($state > 0 ? 'warning' : 'gray')),

                TextColumn::make('expires_at')
                    ->label('Läuft ab')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->isExpired() ? 'Abgelaufen' : 'Noch ' . now()->diffForHumans($record->expires_at, true)),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'expired' => 'Abgelaufen',
                        'verified' => 'Verifiziert',
                        'exceeded' => 'Zu viele Versuche',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'pending' => $query->whereNull('verified_at')
                                ->where('expires_at', '>', now())
                                ->where('attempts', '<', 5),
                            'expired' => $query->whereNull('verified_at')
                                ->where('expires_at', '<=', now()),
                            'verified' => $query->whereNotNull('verified_at'),
                            'exceeded' => $query->whereNull('verified_at')
                                ->where('attempts', '>=', 5),
                            default => $query,
                        };
                    }),

                Filter::make('only_pending')
                    ->label('Nur ausstehende')
                    ->query(fn (Builder $query): Builder => $query->whereNull('verified_at'))
                    ->default(),

                Filter::make('created_today')
                    ->label('Heute erstellt')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()))
                    ->toggle(),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Details')
                    ->icon('heroicon-o-eye'),
                DeleteAction::make()
                    ->label('Löschen')
                    ->modalHeading('Registrierungsversuch löschen')
                    ->modalDescription('Sind Sie sicher? Der Nutzer muss sich erneut registrieren.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Ausgewählte löschen')
                        ->modalHeading('Registrierungsversuche löschen')
                        ->modalDescription('Sind Sie sicher, dass Sie die ausgewählten Einträge löschen möchten?'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-envelope')
            ->emptyStateHeading('Keine ausstehenden Registrierungen')
            ->emptyStateDescription('Es gibt derzeit keine offenen Registrierungsversuche.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPluginEmailVerifications::route('/'),
            'view' => Pages\ViewPluginEmailVerification::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user() && auth()->user()->isAdmin() && auth()->user()->isActive();
    }

    public static function canView(Model $record): bool
    {
        return auth()->user() && auth()->user()->isAdmin() && auth()->user()->isActive();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user() && auth()->user()->isAdmin() && auth()->user()->isActive();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Plugin';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', 5)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected static function getStatusLabel($record): string
    {
        if ($record->verified_at) {
            return 'Verifiziert';
        }
        if ($record->attempts >= 5) {
            return 'Zu viele Versuche';
        }
        if ($record->expires_at->isPast()) {
            return 'Abgelaufen';
        }
        return 'Ausstehend';
    }

    protected static function getStatusColor($record): string
    {
        if ($record->verified_at) {
            return 'success';
        }
        if ($record->attempts >= 5) {
            return 'danger';
        }
        if ($record->expires_at->isPast()) {
            return 'gray';
        }
        return 'warning';
    }
}
