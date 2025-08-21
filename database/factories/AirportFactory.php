<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Airport>
 */
class AirportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $airports = [
            ['name' => 'Frankfurt Airport', 'iata' => 'FRA', 'icao' => 'EDDF'],
            ['name' => 'Munich Airport', 'iata' => 'MUC', 'icao' => 'EDDM'],
            ['name' => 'Berlin Brandenburg Airport', 'iata' => 'BER', 'icao' => 'EDDB'],
            ['name' => 'Hamburg Airport', 'iata' => 'HAM', 'icao' => 'EDDH'],
            ['name' => 'Cologne Bonn Airport', 'iata' => 'CGN', 'icao' => 'EDDK'],
            ['name' => 'Stuttgart Airport', 'iata' => 'STR', 'icao' => 'EDDS'],
            ['name' => 'Düsseldorf Airport', 'iata' => 'DUS', 'icao' => 'EDDL'],
            ['name' => 'Hannover Airport', 'iata' => 'HAJ', 'icao' => 'EDDV'],
            ['name' => 'Nuremberg Airport', 'iata' => 'NUE', 'icao' => 'EDDN'],
            ['name' => 'Leipzig Halle Airport', 'iata' => 'LEJ', 'icao' => 'EDDP'],
        ];

        $airport = fake()->randomElement($airports);

        return [
            'name' => $airport['name'],
            'iata_code' => $airport['iata'],
            'icao_code' => $airport['icao'],
            'type' => fake()->randomElement(['domestic', 'international', 'military']),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'country_id' => Country::factory(),
            'city_id' => City::factory(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the airport is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
