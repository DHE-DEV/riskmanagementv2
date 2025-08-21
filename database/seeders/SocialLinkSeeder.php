<?php

namespace Database\Seeders;

use App\Models\SocialLink;
use Illuminate\Database\Seeder;

class SocialLinkSeeder extends Seeder
{
    public function run(): void
    {
        $examples = [];
        $platforms = ['tiktok','instagram','facebook','youtube'];
        $cities = [
            ['city' => 'Paris', 'country' => 'France', 'lat' => 48.8566, 'lng' => 2.3522],
            ['city' => 'Berlin', 'country' => 'Germany', 'lat' => 52.5200, 'lng' => 13.4050],
            ['city' => 'Rome', 'country' => 'Italy', 'lat' => 41.9028, 'lng' => 12.4964],
            ['city' => 'Barcelona', 'country' => 'Spain', 'lat' => 41.3874, 'lng' => 2.1686],
            ['city' => 'Vienna', 'country' => 'Austria', 'lat' => 48.2082, 'lng' => 16.3738],
            ['city' => 'Prague', 'country' => 'Czech Republic', 'lat' => 50.0755, 'lng' => 14.4378],
            ['city' => 'Amsterdam', 'country' => 'Netherlands', 'lat' => 52.3676, 'lng' => 4.9041],
            ['city' => 'Athens', 'country' => 'Greece', 'lat' => 37.9838, 'lng' => 23.7275],
            ['city' => 'Lisbon', 'country' => 'Portugal', 'lat' => 38.7223, 'lng' => -9.1393],
            ['city' => 'Zurich', 'country' => 'Switzerland', 'lat' => 47.3769, 'lng' => 8.5417],
        ];

        for ($i = 0; $i < 50; $i++) {
            $p = $platforms[$i % count($platforms)];
            $c = $cities[$i % count($cities)];
            $examples[] = [
                'platform' => $p,
                'title' => ucfirst($p) . ' Touristik Tipp #' . ($i+1),
                'url' => 'https://example.com/' . $p . '/tourism-' . ($i+1),
                'description' => 'Inspirierender Reise-Content über ' . $c['city'] . ' (' . $c['country'] . ').',
                'latitude' => $c['lat'],
                'longitude' => $c['lng'],
                'country' => $c['country'],
                'city' => $c['city'],
                'tags' => json_encode(['tourism','travel','sights']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        SocialLink::insert($examples);
    }
}


