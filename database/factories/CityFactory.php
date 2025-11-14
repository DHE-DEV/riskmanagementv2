<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cities = [
            ['name_de' => 'Berlin', 'name_en' => 'Berlin'],
            ['name_de' => 'Hamburg', 'name_en' => 'Hamburg'],
            ['name_de' => 'München', 'name_en' => 'Munich'],
            ['name_de' => 'Köln', 'name_en' => 'Cologne'],
            ['name_de' => 'Frankfurt', 'name_en' => 'Frankfurt'],
            ['name_de' => 'Stuttgart', 'name_en' => 'Stuttgart'],
            ['name_de' => 'Düsseldorf', 'name_en' => 'Dusseldorf'],
            ['name_de' => 'Dortmund', 'name_en' => 'Dortmund'],
            ['name_de' => 'Leipzig', 'name_en' => 'Leipzig'],
            ['name_de' => 'Dresden', 'name_en' => 'Dresden'],
        ];

        $city = fake()->randomElement($cities);

        return [
            'name_translations' => [
                'de' => $city['name_de'],
                'en' => $city['name_en'],
            ],
            'country_id' => Country::factory(),
            'region_id' => Region::factory(),
            'population' => fake()->optional()->numberBetween(50000, 5000000),
            'lat' => fake()->optional()->latitude(),
            'lng' => fake()->optional()->longitude(),
            'is_capital' => fake()->boolean(5),
            'is_regional_capital' => fake()->boolean(10),
        ];
    }
}
