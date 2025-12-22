<?php

namespace Database\Seeders;

use App\Models\InfoSource;
use Illuminate\Database\Seeder;

class InfoSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'name' => 'Auswärtiges Amt',
                'code' => 'auswaertiges-amt',
                'description' => 'Offizielle Reise- und Sicherheitshinweise des deutschen Auswärtigen Amtes',
                'type' => 'rss',
                'url' => 'https://www.auswaertiges-amt.de/de/ReiseUndSicherheit/Rss.xml',
                'content_type' => 'travel_advisory',
                'country_code' => 'DE',
                'language' => 'de',
                'refresh_interval' => 3600,
                'is_active' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'US State Department',
                'code' => 'us-state-dept',
                'description' => 'Travel Advisories des U.S. Department of State',
                'type' => 'api',
                'api_endpoint' => 'https://travel.state.gov/_res/rss/TAsTWs.xml',
                'content_type' => 'travel_advisory',
                'country_code' => 'US',
                'language' => 'en',
                'refresh_interval' => 3600,
                'is_active' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'UK FCDO',
                'code' => 'uk-fcdo',
                'description' => 'Foreign, Commonwealth & Development Office Travel Advice',
                'type' => 'rss',
                'url' => 'https://www.gov.uk/foreign-travel-advice.atom',
                'content_type' => 'travel_advisory',
                'country_code' => 'GB',
                'language' => 'en',
                'refresh_interval' => 3600,
                'is_active' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'WHO Disease Outbreak News',
                'code' => 'who-don',
                'description' => 'World Health Organization Disease Outbreak News und Gesundheitswarnungen',
                'type' => 'rss',
                'url' => 'https://www.who.int/feeds/entity/csr/don/en/rss.xml',
                'content_type' => 'health',
                'country_code' => null,
                'language' => 'en',
                'refresh_interval' => 7200,
                'is_active' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'GDACS',
                'code' => 'gdacs',
                'description' => 'Global Disaster Alert and Coordination System - Naturkatastrophen weltweit',
                'type' => 'rss_api',
                'url' => 'https://www.gdacs.org/xml/rss.xml',
                'api_endpoint' => 'https://www.gdacs.org/gdacsapi/api/events/geteventlist',
                'content_type' => 'disaster',
                'country_code' => null,
                'language' => 'en',
                'refresh_interval' => 1800,
                'is_active' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'ACLED',
                'code' => 'acled',
                'description' => 'Armed Conflict Location & Event Data - Konflikte und politische Unruhen',
                'type' => 'api',
                'api_endpoint' => 'https://api.acleddata.com/acled/read',
                'content_type' => 'conflict',
                'country_code' => null,
                'language' => 'en',
                'refresh_interval' => 86400,
                'is_active' => false,
                'api_config' => [
                    'note' => 'API-Key erforderlich - kostenlose Registrierung auf acleddata.com',
                ],
                'sort_order' => 6,
            ],
        ];

        foreach ($sources as $source) {
            InfoSource::updateOrCreate(
                ['code' => $source['code']],
                $source
            );
        }
    }
}
