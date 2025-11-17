<?php

namespace App\Filament\Resources\Airports\RelationManagers;

use App\Models\Airline;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AirlinesRelationManager extends RelationManager
{
    protected static string $relationship = 'airlines';

    protected static ?string $title = 'Airlines';

    protected static ?string $modelLabel = 'Airline';

    protected static ?string $pluralModelLabel = 'Airlines';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('recordId')
                    ->label('Airline')
                    ->options(fn () => Airline::query()
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(function ($airline) {
                            $label = $airline->name;
                            if ($airline->iata_code) {
                                $label .= ' (' . $airline->iata_code . ')';
                            }
                            return [$airline->id => $label];
                        })
                        ->toArray()
                    )
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => Airline::query()
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('iata_code', 'like', '%' . $search . '%')
                        ->orWhere('icao_code', 'like', '%' . $search . '%')
                        ->orderBy('name')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(function ($airline) {
                            $label = $airline->name;
                            if ($airline->iata_code) {
                                $label .= ' (' . $airline->iata_code . ')';
                            }
                            return [$airline->id => $label];
                        })
                        ->toArray()
                    )
                    ->getOptionLabelUsing(fn ($value) => Airline::find($value)?->name)
                    ->required()
                    ->disabled(fn (string $operation): bool => $operation === 'edit'),

                Select::make('direction')
                    ->label('Richtung')
                    ->options([
                        'both' => 'Abflug und Ankunft',
                        'from' => 'Abflug',
                        'to' => 'Ankunft',
                    ])
                    ->default('both')
                    ->required()
                    ->native(false)
                    ->helperText('Gibt an, in welche Richtung Flüge stattfinden'),

                \Filament\Forms\Components\TextInput::make('terminal')
                    ->label('Terminal')
                    ->maxLength(50)
                    ->placeholder('z.B. Terminal 1, T2, A')
                    ->helperText('Terminal an dem die Airline operiert (optional)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Airline')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('iata_code')
                    ->label('IATA')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('icao_code')
                    ->label('ICAO')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('homeCountry.name_de')
                    ->label('Heimatland')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cabin_classes')
                    ->label('Kabinenklassen')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record->cabin_classes) {
                            return [];
                        }
                        $labels = [
                            'economy' => 'Eco',
                            'premium_economy' => 'PE',
                            'business' => 'Bus',
                            'first' => '1st',
                        ];
                        return collect($record->cabin_classes)->map(fn($class) => $labels[$class] ?? $class)->toArray();
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('pivot.direction')
                    ->label('Richtung')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'both' => 'Abflug & Ankunft',
                        'from' => 'Abflug',
                        'to' => 'Ankunft',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'both' => 'success',
                        'from' => 'info',
                        'to' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.terminal')
                    ->label('Terminal')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktiv',
                        '0' => 'Inaktiv',
                    ]),
            ])
            ->headerActions([
                \Filament\Actions\AttachAction::make()
                    ->label('Airline hinzufügen')
                    ->modalHeading('Airline hinzufügen')
                    ->modalSubmitActionLabel('Hinzufügen')
                    ->modalCancelActionLabel('Abbrechen')
                    ->preloadRecordSelect()
                    ->form(fn (\Filament\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Airline auswählen')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return Airline::where('name', 'like', "%{$search}%")
                                    ->orWhere('iata_code', 'like', "%{$search}%")
                                    ->orWhere('icao_code', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($airline) {
                                        $label = $airline->name;
                                        if ($airline->iata_code) {
                                            $label .= ' (' . $airline->iata_code . ')';
                                        }
                                        return [$airline->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $airline = Airline::find($value);
                                if (!$airline) {
                                    return $value;
                                }
                                $label = $airline->name;
                                if ($airline->iata_code) {
                                    $label .= ' (' . $airline->iata_code . ')';
                                }
                                return $label;
                            })
                            ->placeholder('Airline suchen...'),

                        \Filament\Forms\Components\Select::make('direction')
                            ->label('Richtung')
                            ->options([
                                'both' => 'Abflug und Ankunft',
                                'from' => 'Abflug',
                                'to' => 'Ankunft',
                            ])
                            ->default('both')
                            ->required()
                            ->native(false),

                        \Filament\Forms\Components\TextInput::make('terminal')
                            ->label('Terminal')
                            ->maxLength(50)
                            ->placeholder('z.B. Terminal 1, T2, A'),
                    ]),
            ])
            ->actions([
                \Filament\Actions\Action::make('edit')
                    ->label('Bearbeiten')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Select::make('direction')
                            ->label('Richtung')
                            ->options([
                                'both' => 'Abflug und Ankunft',
                                'from' => 'Abflug',
                                'to' => 'Ankunft',
                            ])
                            ->default('both')
                            ->required()
                            ->native(false)
                            ->helperText('Gibt an, in welche Richtung Flüge stattfinden'),

                        \Filament\Forms\Components\TextInput::make('terminal')
                            ->label('Terminal')
                            ->maxLength(50)
                            ->placeholder('z.B. Terminal 1, T2, A')
                            ->helperText('Terminal an dem die Airline operiert (optional)'),
                    ])
                    ->fillForm(fn ($record): array => [
                        'direction' => $record->pivot->direction ?? 'both',
                        'terminal' => $record->pivot->terminal,
                    ])
                    ->action(function (array $data, $record, $livewire): void {
                        $livewire->getOwnerRecord()->airlines()->updateExistingPivot(
                            $record->id,
                            [
                                'direction' => $data['direction'],
                                'terminal' => $data['terminal'],
                            ]
                        );
                    })
                    ->modalHeading('Airline bearbeiten')
                    ->modalSubmitActionLabel('Speichern')
                    ->modalCancelActionLabel('Abbrechen'),

                \Filament\Actions\DetachAction::make()
                    ->label('Entfernen')
                    ->modalHeading('Airline entfernen')
                    ->modalDescription('Möchten Sie diese Airline wirklich von diesem Flughafen entfernen?')
                    ->modalSubmitActionLabel('Entfernen')
                    ->modalCancelActionLabel('Abbrechen'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DetachBulkAction::make()
                        ->label('Ausgewählte entfernen')
                        ->modalHeading('Airlines entfernen')
                        ->modalDescription('Möchten Sie die ausgewählten Airlines wirklich von diesem Flughafen entfernen?')
                        ->modalSubmitActionLabel('Entfernen')
                        ->modalCancelActionLabel('Abbrechen'),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->emptyStateHeading('Keine Airlines')
            ->emptyStateDescription('Fügen Sie Airlines hinzu, die diesen Flughafen anfliegen.')
            ->emptyStateIcon('heroicon-o-globe-alt');
    }
}
