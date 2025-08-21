<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Continent>
 */
class ContinentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $continents = [
            ['name' => 'Europe', 'code' => 'EU'],
            ['name' => 'Asia', 'code' => 'AS'],
            ['name' => 'Africa', 'code' => 'AF'],
            ['name' => 'North America', 'code' => 'NA'],
            ['name' => 'South America', 'code' => 'SA'],
            ['name' => 'Australia', 'code' => 'AU'],
            ['name' => 'Antarctica', 'code' => 'AN'],
        ];

        $continent = fake()->randomElement($continents);

        return [
            'name' => $continent['name'],
            'code' => $continent['code'],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the continent is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
