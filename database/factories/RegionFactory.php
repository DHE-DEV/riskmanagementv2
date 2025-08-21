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
            'Bavaria', 'Hesse', 'North Rhine-Westphalia', 'Baden-Württemberg',
            'Lower Saxony', 'Saxony', 'Thuringia', 'Brandenburg',
            'Mecklenburg-Vorpommern', 'Schleswig-Holstein', 'Hamburg', 'Berlin',
            'Bremen', 'Saarland', 'Rhineland-Palatinate', 'Saxony-Anhalt'
        ];

        return [
            'name' => fake()->randomElement($regions),
            'country_id' => Country::factory(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the region is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
