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
            ['name' => 'Germany', 'code' => 'DE', 'iso3' => 'DEU'],
            ['name' => 'France', 'code' => 'FR', 'iso3' => 'FRA'],
            ['name' => 'United States', 'code' => 'US', 'iso3' => 'USA'],
            ['name' => 'United Kingdom', 'code' => 'GB', 'iso3' => 'GBR'],
            ['name' => 'Japan', 'code' => 'JP', 'iso3' => 'JPN'],
            ['name' => 'Canada', 'code' => 'CA', 'iso3' => 'CAN'],
            ['name' => 'Australia', 'code' => 'AU', 'iso3' => 'AUS'],
            ['name' => 'Brazil', 'code' => 'BR', 'iso3' => 'BRA'],
            ['name' => 'China', 'code' => 'CN', 'iso3' => 'CHN'],
            ['name' => 'India', 'code' => 'IN', 'iso3' => 'IND'],
        ];

        $country = fake()->randomElement($countries);

        return [
            'name' => $country['name'],
            'code' => $country['code'],
            'iso3' => $country['iso3'],
            'continent_id' => Continent::factory(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the country is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
