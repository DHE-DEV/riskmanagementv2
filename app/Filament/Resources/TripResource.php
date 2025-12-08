<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Models\TravelDetail\TdTrip;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\Builder;

class TripResource extends Resource
{
    protected static ?string $model = TdTrip::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Reisen';

    protected static ?string $modelLabel = 'Reise';

    protected static ?string $pluralModelLabel = 'Reisen';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'travel-detail/trips';

    public static function shouldRegisterNavigation(): bool
    {
        return config('travel_detail.enabled', false);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Travel Management';
    }

    public static function getNavigationBadge(): ?string
    {
        if (!config('travel_detail.enabled', false)) {
            return null;
        }

        return (string) static::getModel()::where('status', 'active')
            ->where('computed_end_at', '>=', now())
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('external_trip_id')
                    ->label('Externe ID')
                    ->searchable()
                    ->copyable()
                    ->limit(20),

                TextColumn::make('provider_id')
                    ->label('Provider')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('booking_reference')
                    ->label('Buchungsref.')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('computed_start_at')
                    ->label('Start')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('computed_end_at')
                    ->label('Ende')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('countries_visited')
                    ->label('Länder')
                    ->badge()
                    ->separator(', ')
                    ->limitList(3)
                    ->color('info'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktiv',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Storniert',
                        default => $state,
                    }),

                TextColumn::make('created_at')
                    ->label('Importiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktiv',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Storniert',
                    ]),

                SelectFilter::make('provider_id')
                    ->label('Provider')
                    ->options(fn () => TdTrip::query()
                        ->distinct()
                        ->pluck('provider_id', 'provider_id')
                        ->toArray()
                    ),

                Filter::make('currently_traveling')
                    ->label('Aktuell unterwegs')
                    ->query(fn (Builder $query) => $query->currentlyTraveling()),

                Filter::make('upcoming')
                    ->label('Bevorstehend')
                    ->query(fn (Builder $query) => $query->upcoming()),

                Filter::make('archived')
                    ->label('Archiviert zeigen')
                    ->query(fn (Builder $query, array $data) =>
                        $data['isActive'] ? $query : $query->where('is_archived', false)
                    )
                    ->toggle()
                    ->default(false),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('computed_start_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Reise-Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('provider_id')
                                    ->label('Provider'),
                                TextEntry::make('external_trip_id')
                                    ->label('Externe ID')
                                    ->copyable(),
                                TextEntry::make('booking_reference')
                                    ->label('Buchungsreferenz')
                                    ->placeholder('-'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('computed_start_at')
                                    ->label('Start')
                                    ->dateTime('d.m.Y H:i'),
                                TextEntry::make('computed_end_at')
                                    ->label('Ende')
                                    ->dateTime('d.m.Y H:i'),
                                TextEntry::make('duration_days')
                                    ->label('Dauer')
                                    ->suffix(' Tage'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'completed' => 'gray',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),
                                ViewEntry::make('countries_visited_display')
                                    ->label('')
                                    ->view('filament.resources.trip-resource.country-badges'),
                            ]),
                    ]),

                Section::make('Reisende')
                    ->schema([
                        TextEntry::make('travellers_info')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                $travellers = $record->travellers;

                                if ($travellers->isEmpty()) {
                                    return '<p style="color: #6b7280; padding: 16px;">Keine Reisenden erfasst</p>';
                                }

                                $html = '<table style="width: 100%; border-collapse: collapse;">';
                                $html .= '<thead>';
                                $html .= '<tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">';
                                $html .= '<th style="text-align: left; padding: 12px 16px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase;">Name</th>';
                                $html .= '<th style="text-align: left; padding: 12px 16px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase;">Typ</th>';
                                $html .= '<th style="text-align: center; padding: 12px 16px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase;">Nationalität</th>';
                                $html .= '<th style="text-align: center; padding: 12px 16px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase;">Pass</th>';
                                $html .= '</tr></thead>';
                                $html .= '<tbody>';

                                $rowIndex = 0;
                                foreach ($travellers as $traveller) {
                                    $rowIndex++;
                                    $rowBg = $rowIndex % 2 === 0 ? '#f9fafb' : '#ffffff';

                                    // Auto-generated travellers (from simple format) show "-" for type
                                    $isAutoGenerated = str_starts_with($traveller->external_traveller_id ?? '', 'AUTO-');
                                    $typeLabel = $isAutoGenerated ? '-' : match ($traveller->traveller_type) {
                                        'adult' => 'Erwachsener',
                                        'child' => 'Kind',
                                        'infant' => 'Kleinkind',
                                        default => ucfirst($traveller->traveller_type ?? '-'),
                                    };

                                    $name = trim("{$traveller->first_name} {$traveller->last_name}") ?: 'Unbekannt';
                                    $nationality = $traveller->nationality ?? '-';
                                    $passport = $traveller->passport_country ?? '-';

                                    $html .= "<tr style=\"background: {$rowBg}; border-bottom: 1px solid #e5e7eb;\">";
                                    $html .= "<td style=\"padding: 12px 16px; font-size: 14px; font-weight: 500; color: #111827;\"><i class=\"fa-solid fa-user\" style=\"color: #6b7280; margin-right: 8px;\"></i>{$name}</td>";
                                    $html .= "<td style=\"padding: 12px 16px;\"><span style=\"display: inline-block; padding: 4px 10px; border-radius: 6px; background: #e5e7eb; font-size: 12px; font-weight: 600; color: #374151;\">{$typeLabel}</span></td>";
                                    $html .= "<td style=\"padding: 12px 16px; text-align: center;\"><span style=\"display: inline-block; padding: 4px 10px; border-radius: 6px; background: #dbeafe; font-size: 12px; font-weight: 700; color: #1e40af;\">{$nationality}</span></td>";
                                    $html .= "<td style=\"padding: 12px 16px; text-align: center;\"><span style=\"display: inline-block; padding: 4px 10px; border-radius: 6px; background: #f3e8ff; font-size: 12px; font-weight: 700; color: #7e22ce;\">{$passport}</span></td>";
                                    $html .= '</tr>';
                                }

                                $html .= '</tbody></table>';
                                return $html;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('PDS Share-Link')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('pds_share_url')
                                    ->label('Share URL')
                                    ->url(fn ($record) => $record->pds_share_url)
                                    ->openUrlInNewTab()
                                    ->placeholder('Kein Share-Link erstellt'),
                                TextEntry::make('pds_tid')
                                    ->label('TID')
                                    ->placeholder('-'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Statistik')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_segments')
                                    ->label('Flugsegmente'),
                                TextEntry::make('total_stays')
                                    ->label('Aufenthalte'),
                                TextEntry::make('airLegs')
                                    ->label('Flugabschnitte')
                                    ->getStateUsing(fn ($record) => $record->airLegs()->count()),
                                TextEntry::make('transfers')
                                    ->label('Umsteige')
                                    ->getStateUsing(fn ($record) => $record->transfers()
                                        ->where('transfer_type', 'airport')
                                        ->count()),
                            ]),
                    ]),

                Section::make('Flugabschnitte')
                    ->schema([
                        TextEntry::make('air_legs_android')
                            ->hiddenLabel()
                            ->getStateUsing(function ($record) {
                                $airLegs = $record->airLegs;

                                if ($airLegs->isEmpty()) {
                                    return '<p style="color: #6b7280; padding: 16px;">Keine Flugabschnitte</p>';
                                }

                                $html = '<div style="display: flex; flex-direction: column; gap: 12px;">';

                                foreach ($airLegs as $leg) {
                                    $route = $leg->route_summary ?? '-';
                                    $startDate = $leg->leg_start_at?->format('d.m.Y') ?? '-';
                                    $startTime = $leg->leg_start_at?->format('H:i') ?? '';
                                    $endDate = $leg->leg_end_at?->format('d.m.Y') ?? '-';
                                    $endTime = $leg->leg_end_at?->format('H:i') ?? '';
                                    $duration = $leg->formatted_duration ?? '-';

                                    $html .= <<<HTML
                                    <div style="background: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.08); overflow: hidden;">
                                        <!-- Header with route -->
                                        <div style="background: linear-gradient(135deg, #4b5563 0%, #374151 100%); padding: 16px 20px; display: flex; align-items: center; gap: 12px;">
                                            <div style="background: rgba(255,255,255,0.15); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fa-solid fa-plane" style="color: #ffffff; font-size: 18px;"></i>
                                            </div>
                                            <div>
                                                <div style="font-size: 18px; font-weight: 600; color: #ffffff; letter-spacing: 0.5px;">{$route}</div>
                                                <div style="font-size: 12px; color: rgba(255,255,255,0.7); margin-top: 2px;">Flugabschnitt</div>
                                            </div>
                                            <div style="margin-left: auto; background: rgba(255,255,255,0.15); padding: 6px 12px; border-radius: 16px;">
                                                <span style="font-size: 13px; font-weight: 600; color: #ffffff;">{$duration}</span>
                                            </div>
                                        </div>
                                        <!-- Content -->
                                        <div style="padding: 16px 20px; display: flex; justify-content: space-between;">
                                            <div style="flex: 1;">
                                                <div style="font-size: 11px; font-weight: 500; color: #9e9e9e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Abflug</div>
                                                <div style="font-size: 20px; font-weight: 500; color: #212121;">{$startTime}</div>
                                                <div style="font-size: 13px; color: #757575; margin-top: 2px;">{$startDate}</div>
                                            </div>
                                            <div style="display: flex; align-items: center; padding: 0 20px;">
                                                <div style="width: 40px; height: 2px; background: #e0e0e0;"></div>
                                                <i class="fa-solid fa-chevron-right" style="color: #bdbdbd; font-size: 12px; margin: 0 8px;"></i>
                                                <div style="width: 40px; height: 2px; background: #e0e0e0;"></div>
                                            </div>
                                            <div style="flex: 1; text-align: right;">
                                                <div style="font-size: 11px; font-weight: 500; color: #9e9e9e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Ankunft</div>
                                                <div style="font-size: 20px; font-weight: 500; color: #212121;">{$endTime}</div>
                                                <div style="font-size: 13px; color: #757575; margin-top: 2px;">{$endDate}</div>
                                            </div>
                                        </div>
                                    </div>
                                    HTML;
                                }

                                $html .= '</div>';
                                return $html;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Aufenthalte')
                    ->schema([
                        TextEntry::make('stays_android')
                            ->hiddenLabel()
                            ->getStateUsing(function ($record) {
                                $stays = $record->stays;

                                if ($stays->isEmpty()) {
                                    return '<p style="color: #6b7280; padding: 16px;">Keine Aufenthalte</p>';
                                }

                                $html = '<div style="display: flex; flex-direction: column; gap: 12px;">';

                                foreach ($stays as $stay) {
                                    $locationName = $stay->location_name ?? 'Unbekannt';
                                    $stayType = $stay->stay_type_label ?? 'Hotel';
                                    $checkInDate = $stay->check_in?->format('d.m.Y') ?? '-';
                                    $checkOutDate = $stay->check_out?->format('d.m.Y') ?? '-';
                                    $country = $stay->country_code ?? '-';

                                    // Calculate nights
                                    $nights = null;
                                    if ($stay->check_in && $stay->check_out) {
                                        $nights = $stay->check_in->startOfDay()->diffInDays($stay->check_out->startOfDay());
                                    }
                                    $nightsText = $nights !== null ? ($nights == 1 ? '1 Nacht' : "{$nights} Nächte") : '-';

                                    $html .= <<<HTML
                                    <div style="background: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.08); overflow: hidden;">
                                        <!-- Header with hotel name -->
                                        <div style="background: linear-gradient(135deg, #4b5563 0%, #374151 100%); padding: 16px 20px; display: flex; align-items: center; gap: 12px;">
                                            <div style="background: rgba(255,255,255,0.15); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fa-solid fa-hotel" style="color: #ffffff; font-size: 18px;"></i>
                                            </div>
                                            <div style="flex: 1; min-width: 0;">
                                                <div style="font-size: 16px; font-weight: 600; color: #ffffff; letter-spacing: 0.3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{$locationName}</div>
                                                <div style="font-size: 12px; color: rgba(255,255,255,0.7); margin-top: 2px;">{$stayType}</div>
                                            </div>
                                            <div style="background: rgba(255,255,255,0.15); padding: 6px 12px; border-radius: 16px;">
                                                <span style="font-size: 13px; font-weight: 600; color: #ffffff;">{$nightsText}</span>
                                            </div>
                                        </div>
                                        <!-- Content -->
                                        <div style="padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                                            <div style="flex: 1;">
                                                <div style="font-size: 11px; font-weight: 500; color: #9e9e9e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Check-in</div>
                                                <div style="font-size: 18px; font-weight: 500; color: #212121;">{$checkInDate}</div>
                                            </div>
                                            <div style="display: flex; align-items: center; padding: 0 20px;">
                                                <div style="width: 30px; height: 2px; background: #e0e0e0;"></div>
                                                <i class="fa-solid fa-moon" style="color: #9e9e9e; font-size: 14px; margin: 0 10px;"></i>
                                                <div style="width: 30px; height: 2px; background: #e0e0e0;"></div>
                                            </div>
                                            <div style="flex: 1; text-align: right;">
                                                <div style="font-size: 11px; font-weight: 500; color: #9e9e9e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Check-out</div>
                                                <div style="font-size: 18px; font-weight: 500; color: #212121;">{$checkOutDate}</div>
                                            </div>
                                            <div style="margin-left: 20px;">
                                                <span style="display: inline-block; padding: 6px 10px; border-radius: 6px; background: #e5e7eb; font-size: 12px; font-weight: 700; color: #374151;">{$country}</span>
                                            </div>
                                        </div>
                                    </div>
                                    HTML;
                                }

                                $html .= '</div>';
                                return $html;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Metadaten')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('schema_version')
                                    ->label('Schema-Version'),
                                TextEntry::make('created_at')
                                    ->label('Erstellt')
                                    ->dateTime('d.m.Y H:i'),
                                TextEntry::make('updated_at')
                                    ->label('Aktualisiert')
                                    ->dateTime('d.m.Y H:i'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Reiseverlauf (Chronologisch)')
                    ->schema([
                        TextEntry::make('timeline')
                            ->hiddenLabel()
                            ->getStateUsing(function ($record) {
                                $timeline = $record->getChronologicalTimeline();

                                if ($timeline->isEmpty()) {
                                    return '<p style="color: #6b7280; padding: 20px 0;">Keine Standortdaten verfügbar</p>';
                                }

                                $html = '<div style="width: 100%; overflow-x: auto;">';
                                $html .= '<table style="width: 100%; border-collapse: collapse; table-layout: fixed; min-width: 950px;">';
                                $html .= '<colgroup>';
                                $html .= '<col style="width: 115px;">';  // Datum (mit 10px padding)
                                $html .= '<col style="width: 95px;">';   // Typ (mit 10px padding links/rechts)
                                $html .= '<col style="width: auto;">';   // Route / Ort
                                $html .= '<col style="width: 70px;">';   // Abflug (Check-in)
                                $html .= '<col style="width: 75px;">';   // Ankunft (Check-out)
                                $html .= '<col style="width: 45px;">';   // Land
                                $html .= '<col style="width: 85px;">';   // Umstieg
                                $html .= '<col style="width: 85px;">';   // Dauer
                                $html .= '</colgroup>';
                                $html .= '<thead>';
                                $html .= '<tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">';
                                $html .= '<th style="text-align: left; padding: 14px 10px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; white-space: nowrap;">Datum</th>';
                                $html .= '<th style="text-align: left; padding: 14px 10px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; white-space: nowrap;">Typ</th>';
                                $html .= '<th style="text-align: left; padding: 14px 10px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; white-space: nowrap;">Route / Ort</th>';
                                $html .= '<th style="text-align: center; padding: 14px 8px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; white-space: nowrap;">Von</th>';
                                $html .= '<th style="text-align: center; padding: 14px 8px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; white-space: nowrap;">Bis</th>';
                                $html .= '<th style="text-align: center; padding: 14px 6px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; white-space: nowrap;">Land</th>';
                                $html .= '<th style="text-align: center; padding: 14px 8px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; white-space: nowrap;">Umstieg</th>';
                                $html .= '<th style="text-align: right; padding: 14px 10px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; white-space: nowrap;">Dauer</th>';
                                $html .= '</tr></thead>';
                                $html .= '<tbody>';

                                $rowIndex = 0;
                                foreach ($timeline as $event) {
                                    $rowIndex++;
                                    $isEven = $rowIndex % 2 === 0;
                                    $rowBg = $isEven ? '#f9fafb' : '#ffffff';

                                    // German day names
                                    $germanDays = ['Sunday' => 'Sonntag', 'Monday' => 'Montag', 'Tuesday' => 'Dienstag', 'Wednesday' => 'Mittwoch', 'Thursday' => 'Donnerstag', 'Friday' => 'Freitag', 'Saturday' => 'Samstag'];

                                    if ($event['type'] === 'flight') {
                                        // Flight row
                                        $typeBadge = '<span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #dbeafe; color: #1e40af; white-space: nowrap;"><i class="fa-solid fa-plane"></i> Flug</span>';
                                        $dateOnly = $event['departure_time']?->format('d.m.Y') ?? '-';
                                        $dayName = $event['departure_time'] ? ($germanDays[$event['departure_time']->format('l')] ?? '') : '';
                                        $date = "{$dateOnly}<br><span style=\"font-size: 11px; color: #6b7280;\">{$dayName}</span>";
                                        $departureTime = $event['departure_time']?->format('H:i') ?? '-';
                                        $arrivalTime = $event['arrival_time']?->format('H:i') ?? '-';
                                        $arrivalDate = $event['arrival_time']?->format('d.m') ?? '';

                                        // Show arrival date if different from departure
                                        if ($event['departure_time'] && $event['arrival_time'] &&
                                            $event['departure_time']->format('Y-m-d') !== $event['arrival_time']->format('Y-m-d')) {
                                            $arrivalTime .= "<br><span style=\"font-size: 10px; color: #6b7280;\">{$arrivalDate}</span>";
                                        }

                                        $route = "<span style=\"font-family: ui-monospace, monospace; font-size: 14px; font-weight: 600; color: #111827;\">{$event['departure_code']} → {$event['arrival_code']}</span>";
                                        // Add airport names in second line
                                        $departureName = $event['departure_name'] ?? '';
                                        $arrivalName = $event['arrival_name'] ?? '';
                                        if ($departureName || $arrivalName) {
                                            $route .= "<br><span style=\"font-size: 11px; color: #6b7280;\">{$departureName} → {$arrivalName}</span>";
                                        }
                                        $country = $event['arrival_country'] ?? '-';
                                        $duration = $event['formatted_duration'];

                                        $transferDisplay = '';
                                        if (!empty($event['transfer_info'])) {
                                            $transferTime = $event['transfer_info']['formatted_time'];
                                            $transferDisplay = "<span style=\"display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 6px; background: #fef3c7; font-size: 11px; font-weight: 600; color: #b45309; white-space: nowrap;\"><i class=\"fa-solid fa-clock\"></i> {$transferTime}</span>";
                                        }

                                        $html .= "<tr style=\"background: {$rowBg}; border-bottom: 1px solid #e5e7eb;\">";
                                        $html .= "<td style=\"padding: 12px 10px; font-size: 13px; font-weight: 500; color: #111827; white-space: nowrap;\">{$date}</td>";
                                        $html .= "<td style=\"padding: 12px 10px;\">{$typeBadge}</td>";
                                        $html .= "<td style=\"padding: 12px 10px;\">{$route}</td>";
                                        $html .= "<td style=\"padding: 12px 8px; text-align: center; font-family: ui-monospace, monospace; font-size: 13px; font-weight: 500; color: #111827;\">{$departureTime}</td>";
                                        $html .= "<td style=\"padding: 12px 8px; text-align: center; font-family: ui-monospace, monospace; font-size: 13px; font-weight: 500; color: #111827;\">{$arrivalTime}</td>";
                                        $html .= "<td style=\"padding: 12px 6px; text-align: center;\"><span style=\"display: inline-block; padding: 4px 8px; border-radius: 6px; background: #e5e7eb; font-size: 11px; font-weight: 700; color: #374151;\">{$country}</span></td>";
                                        $html .= "<td style=\"padding: 12px 8px; text-align: center;\">{$transferDisplay}</td>";
                                        $html .= "<td style=\"padding: 12px 10px; text-align: right; font-size: 13px; font-weight: 600; color: #374151; white-space: nowrap;\">{$duration}</td>";
                                        $html .= '</tr>';
                                    } else {
                                        // Stay/Hotel row
                                        $typeBadge = '<span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #f3e8ff; color: #7e22ce; white-space: nowrap;"><i class="fa-solid fa-hotel"></i> Hotel</span>';
                                        $dateOnly = $event['check_in']?->format('d.m.Y') ?? '-';
                                        $dayName = $event['check_in'] ? ($germanDays[$event['check_in']->format('l')] ?? '') : '';
                                        $date = "{$dateOnly}<br><span style=\"font-size: 11px; color: #6b7280;\">{$dayName}</span>";
                                        $checkOutDate = $event['check_out']?->format('d.m.Y') ?? '-';

                                        $locationName = $event['location_name'] ?? '-';
                                        $location = "<span style=\"font-size: 14px; font-weight: 600; color: #111827;\">{$locationName}</span>";
                                        $country = $event['country_code'] ?? '-';
                                        $duration = $event['formatted_duration'];

                                        // Show checkout date above nights count
                                        $durationWithDate = "<span style=\"font-size: 11px; color: #6b7280;\">bis {$checkOutDate}</span><br><span style=\"font-weight: 600;\">{$duration}</span>";

                                        $html .= "<tr style=\"background: {$rowBg}; border-bottom: 1px solid #e5e7eb;\">";
                                        $html .= "<td style=\"padding: 12px 10px; font-size: 13px; font-weight: 500; color: #111827; white-space: nowrap;\">{$date}</td>";
                                        $html .= "<td style=\"padding: 12px 10px;\">{$typeBadge}</td>";
                                        $html .= "<td style=\"padding: 12px 10px;\">{$location}</td>";
                                        $html .= "<td style=\"padding: 12px 8px; text-align: center; font-size: 11px; color: #6b7280;\">Check-in</td>";
                                        $html .= "<td style=\"padding: 12px 8px; text-align: center; font-size: 11px; color: #6b7280;\">Check-out</td>";
                                        $html .= "<td style=\"padding: 12px 6px; text-align: center;\"><span style=\"display: inline-block; padding: 4px 8px; border-radius: 6px; background: #e5e7eb; font-size: 11px; font-weight: 700; color: #374151;\">{$country}</span></td>";
                                        $html .= "<td style=\"padding: 12px 8px; text-align: center;\"></td>";
                                        $html .= "<td style=\"padding: 12px 10px; text-align: right; font-size: 13px; color: #374151;\">{$durationWithDate}</td>";
                                        $html .= '</tr>';
                                    }
                                }

                                $html .= '</tbody></table></div>';
                                return $html;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('RAW JSON Payload')
                    ->description('Zum Kopieren: In den JSON-Bereich klicken, Strg+A (alles markieren), dann Strg+C (kopieren)')
                    ->schema([
                        TextEntry::make('raw_json_display')
                            ->hiddenLabel()
                            ->getStateUsing(function ($record) {
                                $rawPayload = $record->raw_payload;

                                if (empty($rawPayload)) {
                                    return '<p style="color: #6b7280; padding: 16px;">Kein RAW Payload gespeichert</p>';
                                }

                                $jsonString = json_encode($rawPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                $escapedJson = htmlspecialchars($jsonString, ENT_QUOTES, 'UTF-8');

                                return '<pre style="background: #1f2937; color: #e5e7eb; padding: 16px; border-radius: 8px; overflow-x: auto; font-family: ui-monospace, monospace; font-size: 12px; line-height: 1.5; max-height: 600px; overflow-y: auto; user-select: all; cursor: text;">' . $escapedJson . '</pre>';
                            })
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            'view' => Pages\ViewTrip::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['airLegs', 'stays', 'tripLocations', 'travellers']);
    }

    public static function canCreate(): bool
    {
        return false; // Trips are created via API only
    }
}
