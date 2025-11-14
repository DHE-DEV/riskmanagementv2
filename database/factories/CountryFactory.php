<?php

namespace Database\Factories;

use App\Models\Continent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Country>
 */
class CountryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countries = [
            ['name_de' => 'Deutschland', 'name_en' => 'Germany', 'iso_code' => 'DE', 'iso3_code' => 'DEU'],
            ['name_de' => 'Frankreich', 'name_en' => 'France', 'iso_code' => 'FR', 'iso3_code' => 'FRA'],
            ['name_de' => 'Vereinigte Staaten', 'name_en' => 'United States', 'iso_code' => 'US', 'iso3_code' => 'USA'],
            ['name_de' => 'Vereinigtes Königreich', 'name_en' => 'United Kingdom', 'iso_code' => 'GB', 'iso3_code' => 'GBR'],
            ['name_de' => 'Japan', 'name_en' => 'Japan', 'iso_code' => 'JP', 'iso3_code' => 'JPN'],
            ['name_de' => 'Kanada', 'name_en' => 'Canada', 'iso_code' => 'CA', 'iso3_code' => 'CAN'],
            ['name_de' => 'Australien', 'name_en' => 'Australia', 'iso_code' => 'AU', 'iso3_code' => 'AUS'],
            ['name_de' => 'Brasilien', 'name_en' => 'Brazil', 'iso_code' => 'BR', 'iso3_code' => 'BRA'],
            ['name_de' => 'China', 'name_en' => 'China', 'iso_code' => 'CN', 'iso3_code' => 'CHN'],
            ['name_de' => 'Indien', 'name_en' => 'India', 'iso_code' => 'IN', 'iso3_code' => 'IND'],
        ];

        $country = fake()->randomElement($countries);

        return [
            'name_translations' => [
                'de' => $country['name_de'],
                'en' => $country['name_en'],
            ],
            'iso_code' => $country['iso_code'],
            'iso3_code' => $country['iso3_code'],
            'continent_id' => Continent::factory(),
            'is_eu_member' => fake()->boolean(30),
            'is_schengen_member' => fake()->boolean(25),
            'currency_code' => fake()->optional()->currencyCode(),
            'currency_name' => fake()->optional()->word(),
            'currency_symbol' => fake()->optional()->randomElement(['€', '$', '£', '¥']),
            'phone_prefix' => fake()->optional()->numerify('+##'),
            'timezone' => fake()->optional()->timezone(),
            'population' => fake()->optional()->numberBetween(100000, 1500000000),
            'area_km2' => fake()->optional()->randomFloat(2, 1000, 17000000),
            'lat' => fake()->optional()->latitude(),
            'lng' => fake()->optional()->longitude(),
        ];
    }
}
