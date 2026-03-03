<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class CountryRiskProfileForm
{
    private static function riskLevelOptions(): array
    {
        return [
            1 => '1 - Sehr niedrig',
            2 => '2 - Niedrig',
            3 => '3 - Mittel',
            4 => '4 - Hoch',
            5 => '5 - Sehr hoch',
        ];
    }

    public static function schema(): array
    {
        return [
            Tabs::make('risk_profile_tabs')
                ->tabs([
                    self::securityTab(),
                    self::healthTab(),
                    self::naturalHazardsTab(),
                    self::infrastructureTab(),
                    self::entryTab(),
                    self::climateTab(),
                    self::cultureLawTab(),
                ]),
        ];
    }

    private static function securityTab(): Tab
    {
        return Tab::make('Sicherheit')
            ->icon('heroicon-o-shield-check')
            ->schema([
                Grid::make(2)->schema([
                    Select::make('risk_profile.security.overall_risk_level')
                        ->label('Gesamt-Sicherheitsrisiko')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.security.political_stability')
                        ->label('Politische Stabilität')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.security.crime_level')
                        ->label('Kriminalitätsniveau')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.security.terrorism_risk')
                        ->label('Terrorismusrisiko')
                        ->options(self::riskLevelOptions()),
                ]),
                Textarea::make('risk_profile.security.description')
                    ->label('Beschreibung')
                    ->rows(3),
            ]);
    }

    private static function healthTab(): Tab
    {
        return Tab::make('Gesundheit')
            ->icon('heroicon-o-heart')
            ->schema([
                Grid::make(2)->schema([
                    Select::make('risk_profile.health.health_risk_level')
                        ->label('Gesundheitsrisiko')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.health.healthcare_quality')
                        ->label('Gesundheitsversorgung')
                        ->options(self::riskLevelOptions()),
                ]),
                Grid::make(2)->schema([
                    Toggle::make('risk_profile.health.malaria_risk')
                        ->label('Malaria-Risiko'),
                    Toggle::make('risk_profile.health.drinking_water_safe')
                        ->label('Trinkwasser sicher'),
                ]),
                Textarea::make('risk_profile.health.malaria_description')
                    ->label('Malaria-Beschreibung')
                    ->rows(2)
                    ->visible(fn ($get) => $get('risk_profile.health.malaria_risk')),
                Grid::make(2)->schema([
                    TagsInput::make('risk_profile.health.required_vaccinations')
                        ->label('Pflichtimpfungen')
                        ->placeholder('Impfung eingeben'),
                    TagsInput::make('risk_profile.health.recommended_vaccinations')
                        ->label('Empfohlene Impfungen')
                        ->placeholder('Impfung eingeben'),
                ]),
                Textarea::make('risk_profile.health.description')
                    ->label('Beschreibung')
                    ->rows(3),
            ]);
    }

    private static function naturalHazardsTab(): Tab
    {
        return Tab::make('Naturgefahren')
            ->icon('heroicon-o-bolt')
            ->schema([
                Select::make('risk_profile.natural_hazards.natural_hazard_level')
                    ->label('Gesamt-Naturgefahren')
                    ->options(self::riskLevelOptions()),
                Grid::make(3)->schema([
                    Select::make('risk_profile.natural_hazards.earthquake_risk')
                        ->label('Erdbeben')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.natural_hazards.flood_risk')
                        ->label('Überschwemmungen')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.natural_hazards.hurricane_risk')
                        ->label('Hurrikane/Stürme')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.natural_hazards.volcano_risk')
                        ->label('Vulkane')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.natural_hazards.wildfire_risk')
                        ->label('Waldbrände')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.natural_hazards.tsunami_risk')
                        ->label('Tsunamis')
                        ->options(self::riskLevelOptions()),
                ]),
                Textarea::make('risk_profile.natural_hazards.description')
                    ->label('Beschreibung')
                    ->rows(3),
            ]);
    }

    private static function infrastructureTab(): Tab
    {
        return Tab::make('Infrastruktur')
            ->icon('heroicon-o-building-office')
            ->schema([
                Grid::make(2)->schema([
                    Select::make('risk_profile.infrastructure.infrastructure_level')
                        ->label('Infrastruktur-Niveau')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.infrastructure.road_safety')
                        ->label('Straßensicherheit')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.infrastructure.public_transport_quality')
                        ->label('ÖPNV-Qualität')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.infrastructure.medical_infrastructure')
                        ->label('Medizinische Infrastruktur')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.infrastructure.internet_availability')
                        ->label('Internet-Verfügbarkeit')
                        ->options(self::riskLevelOptions()),
                ]),
                Textarea::make('risk_profile.infrastructure.description')
                    ->label('Beschreibung')
                    ->rows(3),
            ]);
    }

    private static function entryTab(): Tab
    {
        return Tab::make('Einreise')
            ->icon('heroicon-o-identification')
            ->schema([
                Grid::make(2)->schema([
                    Toggle::make('risk_profile.entry.visa_required')
                        ->label('Visum erforderlich'),
                    TextInput::make('risk_profile.entry.passport_validity_months')
                        ->label('Reisepass-Gültigkeit (Monate)')
                        ->numeric()
                        ->minValue(0),
                ]),
                Textarea::make('risk_profile.entry.visa_notes')
                    ->label('Visum-Hinweise')
                    ->rows(2),
                Textarea::make('risk_profile.entry.special_requirements')
                    ->label('Besondere Anforderungen')
                    ->rows(2),
                Textarea::make('risk_profile.entry.description')
                    ->label('Beschreibung')
                    ->rows(3),
            ]);
    }

    private static function climateTab(): Tab
    {
        return Tab::make('Klima')
            ->icon('heroicon-o-sun')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('risk_profile.climate.climate_zone')
                        ->label('Klimazone'),
                    Select::make('risk_profile.climate.extreme_weather_risk')
                        ->label('Extremwetter-Risiko')
                        ->options(self::riskLevelOptions()),
                ]),
                Grid::make(2)->schema([
                    TagsInput::make('risk_profile.climate.best_travel_months')
                        ->label('Beste Reisemonate')
                        ->placeholder('Monat eingeben'),
                    TextInput::make('risk_profile.climate.rainy_season')
                        ->label('Regenzeit'),
                ]),
                Textarea::make('risk_profile.climate.description')
                    ->label('Beschreibung')
                    ->rows(3),
            ]);
    }

    private static function cultureLawTab(): Tab
    {
        return Tab::make('Kultur & Recht')
            ->icon('heroicon-o-scale')
            ->schema([
                Grid::make(2)->schema([
                    Select::make('risk_profile.culture_law.drug_laws_severity')
                        ->label('Drogengesetze (Strenge)')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.culture_law.lgbtq_safety')
                        ->label('LGBTQ+-Sicherheit')
                        ->options(self::riskLevelOptions()),
                    Select::make('risk_profile.culture_law.women_safety')
                        ->label('Sicherheit für Frauen')
                        ->options(self::riskLevelOptions()),
                ]),
                Textarea::make('risk_profile.culture_law.cultural_notes')
                    ->label('Kulturelle Hinweise')
                    ->rows(2),
                Textarea::make('risk_profile.culture_law.legal_warnings')
                    ->label('Rechtliche Warnungen')
                    ->rows(2),
                Grid::make(2)->schema([
                    Textarea::make('risk_profile.culture_law.dress_code_notes')
                        ->label('Kleidungsvorschriften')
                        ->rows(2),
                    Textarea::make('risk_profile.culture_law.alcohol_regulations')
                        ->label('Alkoholbestimmungen')
                        ->rows(2),
                ]),
                Textarea::make('risk_profile.culture_law.description')
                    ->label('Beschreibung')
                    ->rows(3),
            ]);
    }
}
