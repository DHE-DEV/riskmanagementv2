<?php

namespace App\Filament\Resources\DisasterEvents\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DisasterEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('severity')
                    ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'])
                    ->default('low')
                    ->required(),
                Select::make('event_type')
                    ->options([
            'earthquake' => 'Earthquake',
            'hurricane' => 'Hurricane',
            'flood' => 'Flood',
            'wildfire' => 'Wildfire',
            'volcano' => 'Volcano',
            'tsunami' => 'Tsunami',
            'drought' => 'Drought',
            'tornado' => 'Tornado',
            'other' => 'Other',
        ])
                    ->default('other')
                    ->required(),
                TextInput::make('lat')
                    ->numeric(),
                TextInput::make('lng')
                    ->numeric(),
                TextInput::make('radius_km')
                    ->numeric(),
                Select::make('country_id')
                    ->relationship('country', 'id'),
                Select::make('region_id')
                    ->relationship('region', 'id'),
                Select::make('city_id')
                    ->relationship('city', 'id'),
                TextInput::make('affected_areas'),
                DatePicker::make('event_date')
                    ->required(),
                DateTimePicker::make('start_time'),
                DateTimePicker::make('end_time'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('impact_assessment'),
                TextInput::make('travel_recommendations'),
                Textarea::make('official_sources')
                    ->columnSpanFull(),
                Textarea::make('media_coverage')
                    ->columnSpanFull(),
                TextInput::make('tourism_impact'),
                TextInput::make('external_sources')
                    ->required(),
                DateTimePicker::make('last_updated')
                    ->required(),
                TextInput::make('confidence_score')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('processing_status')
                    ->options(['pending' => 'Pending', 'processed' => 'Processed', 'failed' => 'Failed', 'none' => 'None'])
                    ->default('none')
                    ->required(),
                Textarea::make('ai_summary')
                    ->columnSpanFull(),
                Textarea::make('ai_recommendations')
                    ->columnSpanFull(),
                Textarea::make('crisis_communication')
                    ->columnSpanFull(),
                TextInput::make('keywords'),
                TextInput::make('magnitude')
                    ->numeric(),
                Textarea::make('casualties')
                    ->columnSpanFull(),
                Textarea::make('economic_impact')
                    ->columnSpanFull(),
                Textarea::make('infrastructure_damage')
                    ->columnSpanFull(),
                Textarea::make('emergency_response')
                    ->columnSpanFull(),
                Textarea::make('recovery_status')
                    ->columnSpanFull(),
                TextInput::make('external_id'),
                TextInput::make('gdacs_event_id'),
                TextInput::make('gdacs_episode_id'),
                Select::make('gdacs_alert_level')
                    ->options(['Green' => 'Green', 'Orange' => 'Orange', 'Red' => 'Red']),
                TextInput::make('gdacs_alert_score')
                    ->numeric(),
                TextInput::make('gdacs_episode_alert_level'),
                TextInput::make('gdacs_episode_alert_score')
                    ->numeric(),
                TextInput::make('gdacs_event_name'),
                TextInput::make('gdacs_calculation_type'),
                TextInput::make('gdacs_severity_value')
                    ->numeric(),
                TextInput::make('gdacs_severity_unit'),
                Textarea::make('gdacs_severity_text')
                    ->columnSpanFull(),
                TextInput::make('gdacs_population_value')
                    ->numeric(),
                TextInput::make('gdacs_population_unit'),
                Textarea::make('gdacs_population_text')
                    ->columnSpanFull(),
                TextInput::make('gdacs_vulnerability')
                    ->numeric(),
                TextInput::make('gdacs_iso3'),
                TextInput::make('gdacs_country'),
                TextInput::make('gdacs_glide'),
                TextInput::make('gdacs_bbox'),
                Textarea::make('gdacs_cap_url')
                    ->columnSpanFull(),
                Textarea::make('gdacs_icon_url')
                    ->columnSpanFull(),
                TextInput::make('gdacs_version')
                    ->numeric(),
                Toggle::make('gdacs_temporary')
                    ->required(),
                Toggle::make('gdacs_is_current')
                    ->required(),
                TextInput::make('gdacs_duration_weeks')
                    ->numeric(),
                TextInput::make('gdacs_resources'),
                Textarea::make('gdacs_map_image')
                    ->columnSpanFull(),
                Textarea::make('gdacs_map_link')
                    ->columnSpanFull(),
                DateTimePicker::make('gdacs_date_added'),
                DateTimePicker::make('gdacs_date_modified'),
                TextInput::make('weather_conditions'),
                TextInput::make('evacuation_info'),
                TextInput::make('transportation_impact'),
                TextInput::make('accommodation_impact'),
                TextInput::make('communication_status'),
                TextInput::make('health_services_status'),
                TextInput::make('utility_services_status'),
                TextInput::make('border_crossings_status'),
            ]);
    }
}
