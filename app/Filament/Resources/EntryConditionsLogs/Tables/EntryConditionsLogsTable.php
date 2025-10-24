<?php

namespace App\Filament\Resources\EntryConditionsLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EntryConditionsLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Datum/Uhrzeit')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nationality')
                    ->label('Nationalität')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                IconColumn::make('success')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                TextColumn::make('results_count')
                    ->label('Anzahl Ergebnisse')
                    ->sortable()
                    ->default('-'),
                TextColumn::make('filters')
                    ->label('Filter')
                    ->formatStateUsing(function ($state) {
                        // Defensive check: ensure $state is an array
                        if (empty($state) || !is_array($state)) {
                            return '-';
                        }
                        $activeFilters = [];
                        foreach ($state as $key => $value) {
                            if ($value === true) {
                                $activeFilters[] = match($key) {
                                    'passport' => 'Reisepass',
                                    'idCard' => 'Personalausweis',
                                    'tempPassport' => 'Vorl. Reisepass',
                                    'tempIdCard' => 'Vorl. Personalausweis',
                                    'childPassport' => 'Kinderreisepass',
                                    'visaFree' => 'Visumfrei',
                                    'eVisa' => 'E-Visum',
                                    'visaOnArrival' => 'Visum bei Ankunft',
                                    'noInsurance' => 'Keine Versicherung',
                                    'noEntryForm' => 'Kein Einreiseformular',
                                    default => $key
                                };
                            }
                        }
                        return empty($activeFilters) ? '-' : implode(', ', $activeFilters);
                    })
                    ->wrap()
                    ->limit(50),
                TextColumn::make('error_message')
                    ->label('Fehler')
                    ->limit(50)
                    ->default('-')
                    ->color('danger'),
            ])
            ->filters([
                SelectFilter::make('nationality')
                    ->label('Nationalität')
                    ->options(function () {
                        return \DB::table('entry_conditions_logs')
                            ->distinct()
                            ->pluck('nationality', 'nationality')
                            ->toArray();
                    }),
                TernaryFilter::make('success')
                    ->label('Status')
                    ->trueLabel('Erfolgreich')
                    ->falseLabel('Fehlgeschlagen')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
