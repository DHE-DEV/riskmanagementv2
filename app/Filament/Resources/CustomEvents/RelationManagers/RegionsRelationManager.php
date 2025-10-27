<?php

namespace App\Filament\Resources\CustomEvents\RelationManagers;

use App\Models\Region;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RegionsRelationManager extends RelationManager
{
    protected static string $relationship = 'regions';

    protected static ?string $title = 'Regionen';

    protected static ?string $modelLabel = 'Region';

    protected static ?string $pluralModelLabel = 'Regionen';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('recordId')
                    ->label('Region')
                    ->options(fn () => Region::query()
                        ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))")
                        ->limit(100)
                        ->get()
                        ->mapWithKeys(fn (Region $r) => [$r->id => $r->getName('de')])
                        ->toArray()
                    )
                    ->searchable()
                    ->required()
                    ->preload(),

                Toggle::make('use_default_coordinates')
                    ->label('Standard-Koordinaten der Region verwenden')
                    ->default(true),

                Grid::make(2)
                    ->schema([
                        TextInput::make('latitude')
                            ->label('Breitengrad')
                            ->numeric()
                            ->step('any')
                            ->disabled(fn ($get) => (bool) $get('use_default_coordinates')),

                        TextInput::make('longitude')
                            ->label('Längengrad')
                            ->numeric()
                            ->step('any')
                            ->disabled(fn ($get) => (bool) $get('use_default_coordinates')),
                    ]),

                Textarea::make('location_note')
                    ->label('Standort-Notiz')
                    ->rows(2)
                    ->placeholder('z.B. Norden der Region, Hauptstadt, etc.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('region_name')
                    ->label('Region')
                    ->getStateUsing(fn ($record) => $record->getName('de'))
                    ->searchable(query: function ($query, string $search): Builder {
                        return $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) LIKE ?", ["%{$search}%"])
                                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.en')) LIKE ?", ["%{$search}%"])
                                    ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->sortable(query: function ($query, string $direction): Builder {
                        return $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de')) {$direction}");
                    }),

                Tables\Columns\TextColumn::make('country.name_translations')
                    ->label('Land')
                    ->formatStateUsing(fn ($record) => $record->country ? $record->country->getName('de') : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('coordinates')
                    ->label('Koordinaten')
                    ->getStateUsing(function ($record) {
                        if (!$record || !$record->pivot) {
                            return '-';
                        }

                        $lat = $record->pivot->latitude;
                        $lng = $record->pivot->longitude;

                        if (!$lat || !$lng) {
                            return '-';
                        }

                        $coords = "{$lat}, {$lng}";
                        if ($record->pivot->location_note) {
                            $coords .= " ({$record->pivot->location_note})";
                        }

                        return $coords;
                    }),

                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Hinzugefügt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                \Filament\Actions\AttachAction::make()
                    ->label('Region hinzufügen')
                    ->preloadRecordSelect()
                    ->form(fn (\Filament\Actions\AttachAction $action): array => [
                        Select::make('recordId')
                            ->label('Region auswählen')
                            ->options(function () {
                                $ownerRecord = $this->getOwnerRecord();
                                $existingIds = $ownerRecord->regions()->pluck('regions.id')->toArray();

                                return Region::query()
                                    ->whereNotIn('id', $existingIds)
                                    ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name_translations, '$.de'))")
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(fn (Region $r) => [$r->id => $r->getName('de') . ' (' . ($r->country ? $r->country->getName('de') : '') . ')'])
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),

                        Toggle::make('use_default_coordinates')
                            ->label('Standard-Koordinaten verwenden')
                            ->default(true),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('latitude')
                                    ->label('Breitengrad')
                                    ->numeric()
                                    ->step('any')
                                    ->disabled(fn ($get) => (bool) $get('use_default_coordinates')),

                                TextInput::make('longitude')
                                    ->label('Längengrad')
                                    ->numeric()
                                    ->step('any')
                                    ->disabled(fn ($get) => (bool) $get('use_default_coordinates')),
                            ]),

                        Textarea::make('location_note')
                            ->label('Standort-Notiz')
                            ->rows(2),
                    ]),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
