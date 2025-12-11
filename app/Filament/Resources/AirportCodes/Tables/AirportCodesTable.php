<?php

namespace App\Filament\Resources\AirportCodes\Tables;

use App\Models\AirportCode;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AirportCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ident')
                    ->label('Ident')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('iata_code')
                    ->label('IATA')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('icao_code')
                    ->label('ICAO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('municipality')
                    ->label('Stadt')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('iso_country')
                    ->label('Land')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'large_airport' => 'success',
                        'medium_airport' => 'info',
                        'small_airport' => 'warning',
                        'heliport' => 'gray',
                        'seaplane_base' => 'primary',
                        'closed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('continent')
                    ->label('Kontinent')
                    ->formatStateUsing(fn (string $state): string => AirportCode::getContinentOptions()[$state] ?? $state)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('scheduled_service')
                    ->label('Linienflug')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'yes' ? 'success' : 'gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('latitude_deg')
                    ->label('Lat')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('longitude_deg')
                    ->label('Lng')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('elevation_ft')
                    ->label('Höhe (ft)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('iata_code', 'asc')
            ->filters([
                SelectFilter::make('has_iata')
                    ->label('Hat IATA Code')
                    ->options([
                        'yes' => 'Ja - Hat IATA Code',
                        'no' => 'Nein - Kein IATA Code',
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['value'] === 'yes',
                            fn ($q) => $q->whereNotNull('iata_code')->where('iata_code', '!=', '')
                        )->when(
                            $data['value'] === 'no',
                            fn ($q) => $q->where(fn ($q) => $q->whereNull('iata_code')->orWhere('iata_code', ''))
                        );
                    }),
                SelectFilter::make('has_icao')
                    ->label('Hat ICAO Code')
                    ->options([
                        'yes' => 'Ja - Hat ICAO Code',
                        'no' => 'Nein - Kein ICAO Code',
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['value'] === 'yes',
                            fn ($q) => $q->whereNotNull('icao_code')->where('icao_code', '!=', '')
                        )->when(
                            $data['value'] === 'no',
                            fn ($q) => $q->where(fn ($q) => $q->whereNull('icao_code')->orWhere('icao_code', ''))
                        );
                    }),
                \Filament\Tables\Filters\Filter::make('iata_code')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('iata_code')
                            ->label('IATA Code')
                            ->placeholder('z.B. FRA'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['iata_code'],
                            fn ($q, $value) => $q->where('iata_code', 'like', "%{$value}%")
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['iata_code']) {
                            return null;
                        }
                        return 'IATA: ' . $data['iata_code'];
                    }),
                \Filament\Tables\Filters\Filter::make('icao_code')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('icao_code')
                            ->label('ICAO Code')
                            ->placeholder('z.B. EDDF'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['icao_code'],
                            fn ($q, $value) => $q->where('icao_code', 'like', "%{$value}%")
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['icao_code']) {
                            return null;
                        }
                        return 'ICAO: ' . $data['icao_code'];
                    }),
                \Filament\Tables\Filters\Filter::make('iso_country')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('iso_country')
                            ->label('Land (ISO)')
                            ->placeholder('z.B. DE'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['iso_country'],
                            fn ($q, $value) => $q->where('iso_country', 'like', "%{$value}%")
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['iso_country']) {
                            return null;
                        }
                        return 'Land: ' . $data['iso_country'];
                    }),
                SelectFilter::make('type')
                    ->label('Typ')
                    ->options(AirportCode::getTypeOptions()),
                SelectFilter::make('continent')
                    ->label('Kontinent')
                    ->options(AirportCode::getContinentOptions()),
                SelectFilter::make('scheduled_service')
                    ->label('Linienflug')
                    ->options([
                        'yes' => 'Ja',
                        'no' => 'Nein',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
