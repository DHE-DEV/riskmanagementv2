<?php

namespace App\Filament\Resources\UserResource\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('E-Mail-Adresse kopiert!'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn ($record) => $record->is_active ? 'Aktiv' : 'Inaktiv'),

                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Administrator')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('E-Mail verifiziert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Nicht verifiziert'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle Benutzer')
                    ->trueLabel('Nur aktive Benutzer')
                    ->falseLabel('Nur inaktive Benutzer'),

                Tables\Filters\TernaryFilter::make('is_admin')
                    ->label('Administrator')
                    ->placeholder('Alle Benutzer')
                    ->trueLabel('Nur Administratoren')
                    ->falseLabel('Nur normale Benutzer'),

                Tables\Filters\Filter::make('email_verified')
                    ->label('E-Mail-Verifizierung')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'))
                    ->toggle(),

                Tables\Filters\Filter::make('email_not_verified')
                    ->label('E-Mail nicht verifiziert')
                    ->query(fn (Builder $query): Builder => $query->whereNull('email_verified_at'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Bearbeiten')
                    ->icon('heroicon-o-pencil'),

                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'Deaktivieren' : 'Aktivieren')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->is_active ? 'Benutzer deaktivieren?' : 'Benutzer aktivieren?')
                    ->modalDescription(fn ($record) => $record->is_active 
                        ? "Möchten Sie {$record->name} wirklich deaktivieren? Deaktivierte Benutzer können sich nicht anmelden."
                        : "Möchten Sie {$record->name} wirklich aktivieren? Der Benutzer kann sich dann wieder anmelden."
                    )
                    ->modalSubmitActionLabel('Bestätigen')
                    ->modalCancelActionLabel('Abbrechen')
                    ->action(function ($record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->visible(fn ($record) => $record->id !== auth()->id()),

                Tables\Actions\DeleteAction::make()
                    ->label('Löschen')
                    ->icon('heroicon-o-trash')
                    ->visible(fn ($record) => $record->id !== auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Benutzer aktivieren')
                        ->modalDescription('Möchten Sie die ausgewählten Benutzer wirklich aktivieren?')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->id !== auth()->id()) {
                                    $record->update(['is_active' => true]);
                                }
                            });
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Benutzer deaktivieren')
                        ->modalDescription('Möchten Sie die ausgewählten Benutzer wirklich deaktivieren?')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->id !== auth()->id()) {
                                    $record->update(['is_active' => false]);
                                }
                            });
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Benutzer löschen')
                        ->modalDescription('Möchten Sie die ausgewählten Benutzer wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('Keine Benutzer gefunden')
            ->emptyStateDescription('Erstellen Sie den ersten Benutzer, um zu beginnen.')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Benutzer erstellen')
                    ->url(route('filament.admin.resources.users.create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
