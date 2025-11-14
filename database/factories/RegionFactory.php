<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Region>
 */
class RegionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $regions = [
            ['name_de' => 'Bayern', 'name_en' => 'Bavaria', 'code' => 'BY'],
            ['name_de' => 'Hessen', 'name_en' => 'Hesse', 'code' => 'HE'],
            ['name_de' => 'Nordrhein-Westfalen', 'name_en' => 'North Rhine-Westphalia', 'code' => 'NW'],
            ['name_de' => 'Baden-Württemberg', 'name_en' => 'Baden-Württemberg', 'code' => 'BW'],
            ['name_de' => 'Niedersachsen', 'name_en' => 'Lower Saxony', 'code' => 'NI'],
            ['name_de' => 'Sachsen', 'name_en' => 'Saxony', 'code' => 'SN'],
            ['name_de' => 'Thüringen', 'name_en' => 'Thuringia', 'code' => 'TH'],
            ['name_de' => 'Brandenburg', 'name_en' => 'Brandenburg', 'code' => 'BB'],
        ];

        $region = fake()->randomElement($regions);

        return [
            'name_translations' => [
                'de' => $region['name_de'],
                'en' => $region['name_en'],
            ],
            'code' => $region['code'],
            'country_id' => Country::factory(),
            'description' => fake()->optional()->sentence(),
            'lat' => fake()->optional()->latitude(),
            'lng' => fake()->optional()->longitude(),
        ];
    }
}
