<?php

namespace App\Filament\Resources\CustomEvents\RelationManagers;

use App\Models\CustomEvent;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Revisionssichere Versionshistorie eines Ereignisses.
 *
 * Alle Versionen teilen sich eine version_group_uuid; die Liste ist bewusst
 * schreibgeschuetzt - Aenderungen entstehen ausschliesslich ueber "Duplizieren".
 */
class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Versionen';

    protected static ?string $modelLabel = 'Version';

    protected static ?string $pluralModelLabel = 'Versionen';

    public static function getBadge(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->versions()->count();
    }

    public function form(Schema $schema): Schema
    {
        // Versionen werden nie in dieser Liste bearbeitet.
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->heading('Versionen')
            ->description('Jede Bearbeitung entsteht als neue Version. Aeltere Versionen bleiben dauerhaft einsehbar.')
            ->defaultSort('version', 'desc')
            ->columns([
                TextColumn::make('version')
                    ->label('Version')
                    ->badge()
                    ->color(fn (CustomEvent $record): string => $record->is_active ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state): string => 'v' . $state)
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (CustomEvent $record): string => match (true) {
                        $record->is_active => 'Aktiv',
                        $record->superseded_by_id !== null => 'Abgeloest',
                        $record->activated_at !== null => 'Inaktiv',
                        default => 'Entwurf',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Aktiv' => 'success',
                        'Abgeloest' => 'warning',
                        'Entwurf' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('title')
                    ->label('Titel')
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('version_note')
                    ->label('Aenderungsnotiz')
                    ->placeholder('—')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('activated_at')
                    ->label('Aktiviert am')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('noch nie')
                    ->sortable(),

                TextColumn::make('superseded_at')
                    ->label('Abgeloest am')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('updater.name')
                    ->label('Bearbeitet von')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('view_version')
                    ->label('Ansehen')
                    ->icon('heroicon-o-eye')
                    ->url(fn (CustomEvent $record): string => route(
                        'filament.admin.resources.custom-events.view',
                        ['record' => $record],
                    )),

                Action::make('edit_version')
                    ->label('Bearbeiten')
                    ->icon('heroicon-o-pencil-square')
                    // Veroeffentlichte Versionen bleiben unveraendert - sonst
                    // waere die Historie nicht revisionssicher.
                    ->visible(fn (CustomEvent $record): bool => $record->activated_at === null)
                    ->url(fn (CustomEvent $record): string => route(
                        'filament.admin.resources.custom-events.edit',
                        ['record' => $record],
                    )),

                Action::make('activate_version')
                    ->label('Aktivieren')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->visible(fn (CustomEvent $record): bool => ! $record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('Version aktivieren')
                    ->modalDescription('Diese Version wird veroeffentlicht, alle anderen Versionen werden deaktiviert und bleiben als Historie erhalten.')
                    ->action(function (CustomEvent $record) {
                        $record->activateVersion(auth()->id());

                        Notification::make()
                            ->title("Version {$record->version} ist aktiv")
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([])
            ->toolbarActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
