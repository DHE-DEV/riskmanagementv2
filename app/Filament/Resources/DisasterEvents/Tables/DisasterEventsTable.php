<?php

namespace App\Filament\Resources\DisasterEvents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DisasterEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('severity'),
                TextColumn::make('eventType.name')
                    ->label('Event-Typ')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Nicht zugeordnet'),
                TextColumn::make('lat')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('lng')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('radius_km')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('country.id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('region.id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('city.id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('event_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('last_updated')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('confidence_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('processing_status'),
                TextColumn::make('magnitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('external_id')
                    ->searchable(),
                TextColumn::make('gdacs_event_id')
                    ->searchable(),
                TextColumn::make('gdacs_episode_id')
                    ->searchable(),
                TextColumn::make('gdacs_alert_level'),
                TextColumn::make('gdacs_alert_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gdacs_episode_alert_level')
                    ->searchable(),
                TextColumn::make('gdacs_episode_alert_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gdacs_event_name')
                    ->searchable(),
                TextColumn::make('gdacs_calculation_type')
                    ->searchable(),
                TextColumn::make('gdacs_severity_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gdacs_severity_unit')
                    ->searchable(),
                TextColumn::make('gdacs_population_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gdacs_population_unit')
                    ->searchable(),
                TextColumn::make('gdacs_vulnerability')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gdacs_iso3')
                    ->searchable(),
                TextColumn::make('gdacs_country')
                    ->searchable(),
                TextColumn::make('gdacs_glide')
                    ->searchable(),
                TextColumn::make('gdacs_version')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('gdacs_temporary')
                    ->boolean(),
                IconColumn::make('gdacs_is_current')
                    ->boolean(),
                TextColumn::make('gdacs_duration_weeks')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gdacs_date_added')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('gdacs_date_modified')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
