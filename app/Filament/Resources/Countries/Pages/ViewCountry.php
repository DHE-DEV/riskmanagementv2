<?php

namespace App\Filament\Resources\Countries\Pages;

use App\Filament\Resources\Countries\CountryResource;
use App\Models\Country;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\HtmlString;

class ViewCountry extends ViewRecord
{
    protected static string $resource = CountryResource::class;

    protected static ?string $title = 'Land anzeigen';

    public function getTitle(): string
    {
        $countryName = $this->record->getName('de');
        return "Land: {$countryName}";
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 2])
            ->components([
                // Linke Spalte
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Land Details')
                            ->schema([
                                Grid::make(2)->schema([
                                    Placeholder::make('german_name')
                                        ->label('Name (Deutsch)')
                                        ->content(fn ($record) => $record->getName('de')),
                                    Placeholder::make('english_name')
                                        ->label('Name (Englisch)')
                                        ->content(fn ($record) => $record->getName('en')),
                                    Placeholder::make('iso_code')
                                        ->label('ISO Code')
                                        ->content(fn ($record) => $record->iso_code),
                                    Placeholder::make('iso3_code')
                                        ->label('ISO3 Code')
                                        ->content(fn ($record) => $record->iso3_code),
                                    Placeholder::make('is_eu_member')
                                        ->label('EU-Mitglied')
                                        ->content(fn ($record) => $record->is_eu_member ? 'Ja' : 'Nein'),
                                    Placeholder::make('is_schengen_member')
                                        ->label('Schengen-Mitglied')
                                        ->content(fn ($record) => $record->is_schengen_member ? 'Ja' : 'Nein'),
                                ]),
                            ]),
                        Section::make('Geografische Informationen')
                            ->schema([
                                Grid::make(2)->schema([
                                    Placeholder::make('continent')
                                        ->label('Kontinent')
                                        ->content(fn ($record) => $record->continent ? ($record->continent->name_translations['de'] ?? $record->continent->name_translations['en'] ?? $record->continent->code) : 'Nicht verfügbar'),
                                    Placeholder::make('coordinates')
                                        ->label('Koordinaten')
                                        ->content(fn ($record) => $record->lat && $record->lng ? "{$record->lat}, {$record->lng}" : 'Nicht verfügbar'),
                                    Placeholder::make('population')
                                        ->label('Bevölkerung')
                                        ->content(fn ($record) => $record->population ? number_format($record->population) : 'Nicht verfügbar'),
                                    Placeholder::make('area_km2')
                                        ->label('Fläche (km²)')
                                        ->content(fn ($record) => $record->area_km2 ? number_format($record->area_km2) : 'Nicht verfügbar'),
                                ]),
                            ]),
                        Section::make('Wirtschaftliche Informationen')
                            ->schema([
                                Grid::make(2)->schema([
                                    Placeholder::make('currency_code')
                                        ->label('Währungscode')
                                        ->content(fn ($record) => $record->currency_code ?? 'Nicht verfügbar'),
                                    Placeholder::make('currency_name')
                                        ->label('Währungsname')
                                        ->content(fn ($record) => $record->currency_name ?? 'Nicht verfügbar'),
                                    Placeholder::make('currency_symbol')
                                        ->label('Währungssymbol')
                                        ->content(fn ($record) => $record->currency_symbol ?? 'Nicht verfügbar'),
                                    Placeholder::make('phone_prefix')
                                        ->label('Telefonvorwahl')
                                        ->content(fn ($record) => $record->phone_prefix ?? 'Nicht verfügbar'),
                                    Placeholder::make('timezone')
                                        ->label('Zeitzone')
                                        ->content(fn ($record) => $record->timezone ?? 'Nicht verfügbar'),
                                ]),
                            ]),
                    ]),

                // Rechte Spalte — Risikoprofil
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Risikoprofil')
                            ->icon('heroicon-o-shield-exclamation')
                            ->schema([
                                Placeholder::make('risk_overview')
                                    ->label('')
                                    ->content(fn ($record) => $record->has_risk_profile
                                        ? new HtmlString(self::renderRiskOverview($record))
                                        : new HtmlString('<p style="color: #9ca3af;">Kein Risikoprofil hinterlegt.</p>')),
                            ]),

                        // Detail-Sections nur wenn Risikoprofil vorhanden
                        Section::make('Sicherheit')
                            ->icon('heroicon-o-shield-check')
                            ->collapsed()
                            ->visible(fn ($record) => ! empty($record->risk_profile['security'] ?? null))
                            ->schema([
                                Placeholder::make('security_details')
                                    ->label('')
                                    ->content(fn ($record) => new HtmlString(self::renderSecurityDetails($record))),
                            ]),

                        Section::make('Gesundheit')
                            ->icon('heroicon-o-heart')
                            ->collapsed()
                            ->visible(fn ($record) => ! empty($record->risk_profile['health'] ?? null))
                            ->schema([
                                Placeholder::make('health_details')
                                    ->label('')
                                    ->content(fn ($record) => new HtmlString(self::renderHealthDetails($record))),
                            ]),

                        Section::make('Naturgefahren')
                            ->icon('heroicon-o-bolt')
                            ->collapsed()
                            ->visible(fn ($record) => ! empty($record->risk_profile['natural_hazards'] ?? null))
                            ->schema([
                                Placeholder::make('natural_hazards_details')
                                    ->label('')
                                    ->content(fn ($record) => new HtmlString(self::renderNaturalHazardsDetails($record))),
                            ]),

                        Section::make('Infrastruktur')
                            ->icon('heroicon-o-building-office')
                            ->collapsed()
                            ->visible(fn ($record) => ! empty($record->risk_profile['infrastructure'] ?? null))
                            ->schema([
                                Placeholder::make('infrastructure_details')
                                    ->label('')
                                    ->content(fn ($record) => new HtmlString(self::renderInfrastructureDetails($record))),
                            ]),

                        Section::make('Einreise')
                            ->icon('heroicon-o-identification')
                            ->collapsed()
                            ->visible(fn ($record) => ! empty($record->risk_profile['entry'] ?? null))
                            ->schema([
                                Placeholder::make('entry_details')
                                    ->label('')
                                    ->content(fn ($record) => new HtmlString(self::renderEntryDetails($record))),
                            ]),

                        Section::make('Klima')
                            ->icon('heroicon-o-sun')
                            ->collapsed()
                            ->visible(fn ($record) => ! empty($record->risk_profile['climate'] ?? null))
                            ->schema([
                                Placeholder::make('climate_details')
                                    ->label('')
                                    ->content(fn ($record) => new HtmlString(self::renderClimateDetails($record))),
                            ]),

                        Section::make('Kultur & Recht')
                            ->icon('heroicon-o-scale')
                            ->collapsed()
                            ->visible(fn ($record) => ! empty($record->risk_profile['culture_law'] ?? null))
                            ->schema([
                                Placeholder::make('culture_law_details')
                                    ->label('')
                                    ->content(fn ($record) => new HtmlString(self::renderCultureLawDetails($record))),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }

    // ── Rendering Helpers ──────────────────────────────────────────────

    private static function renderRiskOverview($record): string
    {
        $profile = $record->risk_profile ?? [];
        $overallLevel = $record->overall_risk_level;

        $html = '<div style="margin-bottom: 1.5rem;">';
        $html .= self::renderRiskBadge($overallLevel, 'Gesamt-Risiko', true);
        $html .= '</div>';

        $categories = [
            ['key' => 'security', 'field' => 'overall_risk_level', 'label' => 'Sicherheit'],
            ['key' => 'health', 'field' => 'health_risk_level', 'label' => 'Gesundheit'],
            ['key' => 'natural_hazards', 'field' => 'natural_hazard_level', 'label' => 'Naturgefahren'],
            ['key' => 'infrastructure', 'field' => 'infrastructure_level', 'label' => 'Infrastruktur'],
            ['key' => 'climate', 'field' => 'extreme_weather_risk', 'label' => 'Extremwetter'],
            ['key' => 'culture_law', 'field' => 'drug_laws_severity', 'label' => 'Kultur & Recht'],
        ];

        $html .= '<div style="display: flex; flex-direction: column; gap: 0.625rem;">';
        foreach ($categories as $cat) {
            $level = $profile[$cat['key']][$cat['field']] ?? null;
            if ($level !== null) {
                $html .= self::renderRiskBar($cat['label'], (int) $level);
            }
        }
        $html .= '</div>';

        return $html;
    }

    private static function renderRiskBadge(?int $level, string $label = '', bool $large = false): string
    {
        $text = Country::getRiskLevelLabel($level);
        $colorName = Country::getRiskLevelColor($level);
        $colors = self::getColorValues($colorName);
        $size = $large ? 'font-size: 1rem; padding: 0.5rem 1rem;' : 'font-size: 0.75rem; padding: 0.25rem 0.625rem;';

        $html = '';
        if ($label) {
            $weight = $large ? 'font-weight: 600; font-size: 0.875rem;' : '';
            $html .= "<span style=\"{$weight} margin-right: 0.5rem;\">{$label}:</span>";
        }
        $html .= "<span style=\"display: inline-block; {$size} border-radius: 9999px; font-weight: 600; color: {$colors['text']}; background-color: {$colors['bg']};\">{$text}</span>";

        return $html;
    }

    private static function renderRiskBar(string $label, int $level): string
    {
        $colorName = Country::getRiskLevelColor($level);
        $colors = self::getColorValues($colorName);
        $percentage = ($level / 5) * 100;
        $levelLabel = Country::getRiskLevelLabel($level);

        return <<<HTML
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="min-width: 7rem; font-size: 0.8125rem; color: #6b7280;">{$label}</span>
            <div style="flex: 1; height: 0.5rem; background-color: #e5e7eb; border-radius: 9999px; overflow: hidden;">
                <div style="width: {$percentage}%; height: 100%; background-color: {$colors['bar']}; border-radius: 9999px;"></div>
            </div>
            <span style="min-width: 5.5rem; font-size: 0.75rem; color: {$colors['text']}; font-weight: 500; text-align: right;">{$levelLabel}</span>
        </div>
        HTML;
    }

    private static function renderDetailRow(string $label, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return "<div style=\"display: flex; justify-content: space-between; padding: 0.375rem 0; border-bottom: 1px solid #f3f4f6;\"><span style=\"color: #6b7280; font-size: 0.8125rem;\">{$label}</span><span style=\"font-size: 0.8125rem; font-weight: 500;\">{$value}</span></div>";
    }

    private static function renderRiskRow(string $label, ?int $level): string
    {
        if ($level === null) {
            return '';
        }
        $text = Country::getRiskLevelLabel($level);
        $colorName = Country::getRiskLevelColor($level);
        $colors = self::getColorValues($colorName);
        return "<div style=\"display: flex; justify-content: space-between; padding: 0.375rem 0; border-bottom: 1px solid #f3f4f6;\"><span style=\"color: #6b7280; font-size: 0.8125rem;\">{$label}</span><span style=\"font-size: 0.75rem; font-weight: 600; color: {$colors['text']}; background-color: {$colors['bg']}; padding: 0.125rem 0.5rem; border-radius: 9999px;\">{$text}</span></div>";
    }

    private static function renderDescription(?string $description): string
    {
        if (! $description) {
            return '';
        }
        $escaped = e($description);
        return "<div style=\"margin-top: 0.75rem; padding: 0.75rem; background-color: #f9fafb; border-radius: 0.5rem; font-size: 0.8125rem; color: #374151; line-height: 1.5;\">{$escaped}</div>";
    }

    private static function renderTagList(string $label, ?array $items): string
    {
        if (empty($items)) {
            return '';
        }
        $tags = implode('', array_map(fn ($item) => "<span style=\"display: inline-block; padding: 0.125rem 0.5rem; background-color: #e5e7eb; border-radius: 9999px; font-size: 0.75rem; margin: 0.125rem;\">" . e($item) . "</span>", $items));
        return "<div style=\"padding: 0.375rem 0; border-bottom: 1px solid #f3f4f6;\"><span style=\"color: #6b7280; font-size: 0.8125rem; display: block; margin-bottom: 0.25rem;\">{$label}</span><div>{$tags}</div></div>";
    }

    // ── Category Detail Renderers ──────────────────────────────────────

    private static function renderSecurityDetails($record): string
    {
        $s = $record->risk_profile['security'] ?? [];
        $html = '<div>';
        $html .= self::renderRiskRow('Gesamt-Sicherheitsrisiko', $s['overall_risk_level'] ?? null);
        $html .= self::renderRiskRow('Politische Stabilität', $s['political_stability'] ?? null);
        $html .= self::renderRiskRow('Kriminalitätsniveau', $s['crime_level'] ?? null);
        $html .= self::renderRiskRow('Terrorismusrisiko', $s['terrorism_risk'] ?? null);
        $html .= self::renderDescription($s['description'] ?? null);
        $html .= '</div>';
        return $html;
    }

    private static function renderHealthDetails($record): string
    {
        $h = $record->risk_profile['health'] ?? [];
        $html = '<div>';
        $html .= self::renderRiskRow('Gesundheitsrisiko', $h['health_risk_level'] ?? null);
        $html .= self::renderRiskRow('Gesundheitsversorgung', $h['healthcare_quality'] ?? null);
        $html .= self::renderDetailRow('Malaria-Risiko', isset($h['malaria_risk']) ? ($h['malaria_risk'] ? 'Ja' : 'Nein') : null);
        if (! empty($h['malaria_description'])) {
            $html .= self::renderDetailRow('Malaria-Info', $h['malaria_description']);
        }
        $html .= self::renderDetailRow('Trinkwasser sicher', isset($h['drinking_water_safe']) ? ($h['drinking_water_safe'] ? 'Ja' : 'Nein') : null);
        $html .= self::renderTagList('Pflichtimpfungen', $h['required_vaccinations'] ?? null);
        $html .= self::renderTagList('Empfohlene Impfungen', $h['recommended_vaccinations'] ?? null);
        $html .= self::renderDescription($h['description'] ?? null);
        $html .= '</div>';
        return $html;
    }

    private static function renderNaturalHazardsDetails($record): string
    {
        $n = $record->risk_profile['natural_hazards'] ?? [];
        $html = '<div>';
        $html .= self::renderRiskRow('Gesamt-Naturgefahren', $n['natural_hazard_level'] ?? null);
        $html .= self::renderRiskRow('Erdbeben', $n['earthquake_risk'] ?? null);
        $html .= self::renderRiskRow('Überschwemmungen', $n['flood_risk'] ?? null);
        $html .= self::renderRiskRow('Hurrikane/Stürme', $n['hurricane_risk'] ?? null);
        $html .= self::renderRiskRow('Vulkane', $n['volcano_risk'] ?? null);
        $html .= self::renderRiskRow('Waldbrände', $n['wildfire_risk'] ?? null);
        $html .= self::renderRiskRow('Tsunamis', $n['tsunami_risk'] ?? null);
        $html .= self::renderDescription($n['description'] ?? null);
        $html .= '</div>';
        return $html;
    }

    private static function renderInfrastructureDetails($record): string
    {
        $i = $record->risk_profile['infrastructure'] ?? [];
        $html = '<div>';
        $html .= self::renderRiskRow('Infrastruktur-Niveau', $i['infrastructure_level'] ?? null);
        $html .= self::renderRiskRow('Straßensicherheit', $i['road_safety'] ?? null);
        $html .= self::renderRiskRow('ÖPNV-Qualität', $i['public_transport_quality'] ?? null);
        $html .= self::renderRiskRow('Medizinische Infrastruktur', $i['medical_infrastructure'] ?? null);
        $html .= self::renderRiskRow('Internet-Verfügbarkeit', $i['internet_availability'] ?? null);
        $html .= self::renderDescription($i['description'] ?? null);
        $html .= '</div>';
        return $html;
    }

    private static function renderEntryDetails($record): string
    {
        $e = $record->risk_profile['entry'] ?? [];
        $html = '<div>';
        $html .= self::renderDetailRow('Visum erforderlich', isset($e['visa_required']) ? ($e['visa_required'] ? 'Ja' : 'Nein') : null);
        $html .= self::renderDetailRow('Reisepass-Gültigkeit', isset($e['passport_validity_months']) ? $e['passport_validity_months'] . ' Monate' : null);
        if (! empty($e['visa_notes'])) {
            $html .= self::renderDetailRow('Visum-Hinweise', $e['visa_notes']);
        }
        if (! empty($e['special_requirements'])) {
            $html .= self::renderDetailRow('Besondere Anforderungen', $e['special_requirements']);
        }
        $html .= self::renderDescription($e['description'] ?? null);
        $html .= '</div>';
        return $html;
    }

    private static function renderClimateDetails($record): string
    {
        $c = $record->risk_profile['climate'] ?? [];
        $html = '<div>';
        $html .= self::renderDetailRow('Klimazone', $c['climate_zone'] ?? null);
        $html .= self::renderRiskRow('Extremwetter-Risiko', $c['extreme_weather_risk'] ?? null);
        $html .= self::renderTagList('Beste Reisemonate', $c['best_travel_months'] ?? null);
        $html .= self::renderDetailRow('Regenzeit', $c['rainy_season'] ?? null);
        $html .= self::renderDescription($c['description'] ?? null);
        $html .= '</div>';
        return $html;
    }

    private static function renderCultureLawDetails($record): string
    {
        $cl = $record->risk_profile['culture_law'] ?? [];
        $html = '<div>';
        $html .= self::renderRiskRow('Drogengesetze (Strenge)', $cl['drug_laws_severity'] ?? null);
        $html .= self::renderRiskRow('LGBTQ+-Sicherheit', $cl['lgbtq_safety'] ?? null);
        $html .= self::renderRiskRow('Sicherheit für Frauen', $cl['women_safety'] ?? null);
        if (! empty($cl['cultural_notes'])) {
            $html .= self::renderDetailRow('Kulturelle Hinweise', $cl['cultural_notes']);
        }
        if (! empty($cl['legal_warnings'])) {
            $html .= self::renderDetailRow('Rechtliche Warnungen', $cl['legal_warnings']);
        }
        if (! empty($cl['dress_code_notes'])) {
            $html .= self::renderDetailRow('Kleidungsvorschriften', $cl['dress_code_notes']);
        }
        if (! empty($cl['alcohol_regulations'])) {
            $html .= self::renderDetailRow('Alkoholbestimmungen', $cl['alcohol_regulations']);
        }
        $html .= self::renderDescription($cl['description'] ?? null);
        $html .= '</div>';
        return $html;
    }

    // ── Color Mapping ──────────────────────────────────────────────────

    private static function getColorValues(string $colorName): array
    {
        return match ($colorName) {
            'success' => ['text' => '#166534', 'bg' => '#dcfce7', 'bar' => '#22c55e'],
            'info' => ['text' => '#1e40af', 'bg' => '#dbeafe', 'bar' => '#3b82f6'],
            'warning' => ['text' => '#92400e', 'bg' => '#fef3c7', 'bar' => '#f59e0b'],
            'danger' => ['text' => '#991b1b', 'bg' => '#fee2e2', 'bar' => '#ef4444'],
            default => ['text' => '#374151', 'bg' => '#f3f4f6', 'bar' => '#9ca3af'],
        };
    }
}
