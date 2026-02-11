<?php

namespace App\Filament\Resources\CustomEvents\Tables;

use App\Models\CustomEvent;
use App\Models\Country;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class CustomEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('countries.name_translations')
                    ->label('Länder')
                    ->getStateUsing(function ($record) {
                        $countries = $record->countries;
                        if ($countries->isEmpty()) {
                            return 'Nicht zugeordnet';
                        }
                        return $countries->map(fn($country) => $country->getName('de'))->implode(', ');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('countries', function ($q) use ($search) {
                            $q->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.de"))) LIKE ?', ['%' . strtolower($search) . '%'])
                              ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.en"))) LIKE ?', ['%' . strtolower($search) . '%']);
                        });
                    })
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->wrap(),

                TextColumn::make('eventTypes.name')
                    ->label('Typen')
                    ->badge()
                    ->separator(', ')
                    ->searchable()
                    ->placeholder('Nicht zugeordnet'),

                TextColumn::make('category')
                    ->label('Kategorie')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('priority')
                    ->label('Priorität')
                    ->formatStateUsing(fn (string $state): string => CustomEvent::getPriorityOptions()[$state] ?? $state),
                
                TextColumn::make('start_date')
                    ->label('Start')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                
                TextColumn::make('end_date')
                    ->label('Ende')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                
                TextColumn::make('is_active')
                    ->label('Aktiv')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ja' : 'Nein')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                TextColumn::make('review_status')
                    ->label('Review')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending_review' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Freigegeben',
                        'pending_review' => 'Ausstehend',
                        'rejected' => 'Abgelehnt',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('apiClient.name')
                    ->label('API-Quelle')
                    ->placeholder('Intern')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('archived')
                    ->label('Archiviert')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ja' : 'Nein')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('archived_at')
                    ->label('Archiviert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Click Statistics Columns
                TextColumn::make('clicks_count')
                    ->label('Gesamt-Klicks')
                    ->counts('clicks')
                    ->badge()
                    ->color(fn (int $state): string => match(true) {
                        $state === 0 => 'gray',
                        $state < 10 => 'info',
                        $state < 50 => 'success',
                        $state < 100 => 'warning',
                        default => 'danger'
                    })
                    ->sortable(),

                TextColumn::make('clicks_today')
                    ->label('Heute')
                    ->getStateUsing(function ($record) {
                        return $record->clicks()
                            ->whereDate('clicked_at', today())
                            ->count();
                    })
                    ->badge()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('clicks_this_week')
                    ->label('Diese Woche')
                    ->getStateUsing(function ($record) {
                        return $record->clicks()
                            ->whereBetween('clicked_at', [now()->startOfWeek(), now()->endOfWeek()])
                            ->count();
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_click')
                    ->label('Letzter Klick')
                    ->getStateUsing(function ($record) {
                        $lastClick = $record->clicks()
                            ->orderBy('clicked_at', 'desc')
                            ->first();
                        return $lastClick?->clicked_at;
                    })
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Noch keine Klicks')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('country_id')
                    ->label('Land')
                    ->options(function () {
                        return Country::query()
                            ->orderByRaw('JSON_UNQUOTE(JSON_EXTRACT(name_translations, "$.de"))')
                            ->get()
                            ->mapWithKeys(fn (Country $country) => [$country->id => $country->getName('de')])
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple()
                    ->preload(),

                SelectFilter::make('event_type_id')
                    ->label('Event-Typ')
                    ->relationship('eventType', 'name')
                    ->multiple(),

                SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options(CustomEvent::getPriorityOptions())
                    ->multiple(),
                
                TernaryFilter::make('is_active')
                    ->label('Nur aktive Events')
                    ->placeholder('Alle Events')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),

                TernaryFilter::make('archived')
                    ->label('Archivierte Events')
                    ->placeholder('Alle Events')
                    ->trueLabel('Nur archivierte')
                    ->falseLabel('Nur nicht-archivierte'),

                SelectFilter::make('review_status')
                    ->label('Review-Status')
                    ->options([
                        'approved' => 'Freigegeben',
                        'pending_review' => 'Ausstehend',
                        'rejected' => 'Abgelehnt',
                    ]),

                Filter::make('start_date')
                    ->label('Startdatum')
                    ->form([
                        DatePicker::make('start_from')
                            ->label('Start von')
                            ->displayFormat('d.m.Y')
                            ->placeholder('TT.MM.JJJJ'),
                        DatePicker::make('start_to')
                            ->label('Start bis')
                            ->displayFormat('d.m.Y')
                            ->placeholder('TT.MM.JJJJ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $indicators = [];
                        if ($data['start_from'] ?? null) {
                            $indicators[] = 'Start ab: ' . \Carbon\Carbon::parse($data['start_from'])->format('d.m.Y');
                        }
                        if ($data['start_to'] ?? null) {
                            $indicators[] = 'Start bis: ' . \Carbon\Carbon::parse($data['start_to'])->format('d.m.Y');
                        }
                        return count($indicators) ? implode(', ', $indicators) : null;
                    }),

                Filter::make('end_date')
                    ->label('Enddatum')
                    ->form([
                        DatePicker::make('end_from')
                            ->label('Ende von')
                            ->displayFormat('d.m.Y')
                            ->placeholder('TT.MM.JJJJ'),
                        DatePicker::make('end_to')
                            ->label('Ende bis')
                            ->displayFormat('d.m.Y')
                            ->placeholder('TT.MM.JJJJ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['end_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '>=', $date),
                            )
                            ->when(
                                $data['end_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $indicators = [];
                        if ($data['end_from'] ?? null) {
                            $indicators[] = 'Ende ab: ' . \Carbon\Carbon::parse($data['end_from'])->format('d.m.Y');
                        }
                        if ($data['end_to'] ?? null) {
                            $indicators[] = 'Ende bis: ' . \Carbon\Carbon::parse($data['end_to'])->format('d.m.Y');
                        }
                        return count($indicators) ? implode(', ', $indicators) : null;
                    }),

                Filter::make('created_at')
                    ->label('Erstellungsdatum')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Erstellt von')
                            ->displayFormat('d.m.Y')
                            ->placeholder('TT.MM.JJJJ'),
                        DatePicker::make('created_to')
                            ->label('Erstellt bis')
                            ->displayFormat('d.m.Y')
                            ->placeholder('TT.MM.JJJJ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Erstellt ab: ' . \Carbon\Carbon::parse($data['created_from'])->format('d.m.Y');
                        }
                        if ($data['created_to'] ?? null) {
                            $indicators[] = 'Erstellt bis: ' . \Carbon\Carbon::parse($data['created_to'])->format('d.m.Y');
                        }
                        return count($indicators) ? implode(', ', $indicators) : null;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Anzeigen'),
                EditAction::make()
                    ->label('Bearbeiten'),
                Action::make('approve')
                    ->label('Freigeben')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CustomEvent $record): bool => $record->review_status === 'pending_review')
                    ->requiresConfirmation()
                    ->action(function (CustomEvent $record) {
                        $record->approve(auth()->id());
                        Notification::make()
                            ->title('Event freigegeben')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Ablehnen')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (CustomEvent $record): bool => $record->review_status === 'pending_review')
                    ->requiresConfirmation()
                    ->action(function (CustomEvent $record) {
                        $record->reject(auth()->id());
                        Notification::make()
                            ->title('Event abgelehnt')
                            ->warning()
                            ->send();
                    }),
                Action::make('duplicate')
                    ->label('Duplizieren')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (CustomEvent $record) {
                        $newEvent = $record->replicate(['clicks_count']);
                        $newEvent->title = $record->title . ' (Kopie)';
                        $newEvent->created_by = auth()->id();
                        $newEvent->updated_by = auth()->id();
                        $newEvent->save();

                        // Copy event types relationship
                        if ($record->eventTypes()->exists()) {
                            $newEvent->eventTypes()->sync($record->eventTypes->pluck('id'));
                        }

                        return redirect()->route('filament.admin.resources.custom-events.edit', $newEvent);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Event duplizieren')
                    ->modalSubheading('Möchten Sie dieses Event wirklich duplizieren?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Löschen'),
                    ForceDeleteBulkAction::make()
                        ->label('Endgültig löschen'),
                    RestoreBulkAction::make()
                        ->label('Wiederherstellen'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->recordUrl(
                fn (CustomEvent $record): string => route('filament.admin.resources.custom-events.view', ['record' => $record])
            );
    }
}
