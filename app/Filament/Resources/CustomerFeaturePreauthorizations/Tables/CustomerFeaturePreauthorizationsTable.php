<?php

namespace App\Filament\Resources\CustomerFeaturePreauthorizations\Tables;

use App\Models\CustomerFeatureOverride;
use App\Models\CustomerFeaturePreauthorization;
use App\Services\CustomerFeaturePreauthorizationService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CustomerFeaturePreauthorizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // withCount statt Unterabfrage je Zeile: die Spalte "Konten" zeigt,
            // ob die Vormerkung schon jemanden betrifft.
            ->modifyQueryUsing(fn ($query) => $query->withCount('customers'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('pds_account_id')
                    ->label('PDS Account-ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('feature_key')
                    ->label('Feature')
                    ->formatStateUsing(fn (string $state): string => CustomerFeatureOverride::getFeatureLabels()[$state] ?? $state)
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                IconColumn::make('enabled')
                    ->label('Freischalten')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-no-symbol')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('customers_count')
                    ->label('Konten')
                    ->badge()
                    ->color(fn ($state): string => (int) $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state): string => (int) $state > 0 ? (string) (int) $state : 'noch keins')
                    ->sortable()
                    ->tooltip('Kundenkonten mit dieser Account-ID'),

                TextColumn::make('applied_at')
                    ->label('Eingeloest')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('offen')
                    ->color(fn ($state): string => $state ? 'success' : 'warning')
                    ->sortable(),

                TextColumn::make('note')
                    ->label('Notiz')
                    ->limit(40)
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Angelegt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('feature_key')
                    ->label('Feature')
                    ->options(CustomerFeatureOverride::getFeatureLabels()),

                TernaryFilter::make('enabled')
                    ->label('Freischalten')
                    ->trueLabel('Nur Freischaltungen')
                    ->falseLabel('Nur Sperren'),

                Filter::make('pending')
                    ->label('Nur offene')
                    ->query(fn ($query) => $query->whereNull('applied_at')),

                Filter::make('without_account')
                    ->label('Ohne Kundenkonto')
                    ->query(fn ($query) => $query->whereDoesntHave('customers')),
            ])
            ->recordActions([
                Action::make('apply')
                    ->label('Jetzt anwenden')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Vormerkung jetzt anwenden')
                    ->modalDescription('Setzt das Feature fuer alle bestehenden Konten dieser Account-ID. Bereits im Kunden gesetzte Werte bleiben unangetastet.')
                    // Ohne Konto gibt es nichts anzuwenden - dann greift der Login.
                    ->visible(fn (CustomerFeaturePreauthorization $record): bool => ($record->customers_count ?? 0) > 0)
                    ->action(function (CustomerFeaturePreauthorization $record): void {
                        $result = app(CustomerFeaturePreauthorizationService::class)
                            ->applyToExistingCustomers($record->feature_key, [$record->pds_account_id]);

                        self::notifyResult($result);
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('apply')
                        ->label('Auswahl anwenden')
                        ->icon('heroicon-o-bolt')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Setzt die Features fuer alle bestehenden Konten der ausgewaehlten Account-IDs. Bereits gesetzte Werte bleiben unangetastet.')
                        ->action(function (Collection $records): void {
                            $service = app(CustomerFeaturePreauthorizationService::class);
                            $total = ['customers' => 0, 'applied' => 0];

                            // Nach Feature gruppieren, weil der Service je Aufruf
                            // genau einen Feature-Key filtert.
                            foreach ($records->groupBy('feature_key') as $featureKey => $group) {
                                $result = $service->applyToExistingCustomers(
                                    $featureKey,
                                    $group->pluck('pds_account_id')->all(),
                                );

                                $total['customers'] += $result['customers'];
                                $total['applied'] += $result['applied'];
                            }

                            self::notifyResult($total);
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->modalDescription('Loescht nur die Vormerkungen. Bereits erteilte Freischaltungen bleiben bestehen.'),
                ]),
            ])
            ->emptyStateHeading('Keine Vormerkungen')
            ->emptyStateDescription('Ueber "IDs importieren" lassen sich ganze Account-Listen auf einmal vormerken.');
    }

    /**
     * @param  array{customers: int, applied: int}  $result
     */
    private static function notifyResult(array $result): void
    {
        if ($result['customers'] === 0) {
            Notification::make()
                ->title('Nichts zu tun')
                ->body('Alle betroffenen Konten haben bereits einen gesetzten Wert oder es gibt noch keine Konten.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Angewendet')
            ->body("{$result['customers']} Konten angepasst ({$result['applied']} Freischaltungen).")
            ->success()
            ->send();
    }
}
