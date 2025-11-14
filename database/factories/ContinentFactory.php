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
            ['name_de' => 'Europa', 'name_en' => 'Europe', 'code' => 'EU'],
            ['name_de' => 'Asien', 'name_en' => 'Asia', 'code' => 'AS'],
            ['name_de' => 'Afrika', 'name_en' => 'Africa', 'code' => 'AF'],
            ['name_de' => 'Nordamerika', 'name_en' => 'North America', 'code' => 'NA'],
            ['name_de' => 'Südamerika', 'name_en' => 'South America', 'code' => 'SA'],
            ['name_de' => 'Ozeanien', 'name_en' => 'Oceania', 'code' => 'OC'],
            ['name_de' => 'Antarktis', 'name_en' => 'Antarctica', 'code' => 'AN'],
        ];

        $continent = fake()->randomElement($continents);

        return [
            'name_translations' => [
                'de' => $continent['name_de'],
                'en' => $continent['name_en'],
            ],
            'code' => $continent['code'],
            'sort_order' => fake()->numberBetween(0, 100),
            'description' => fake()->optional()->sentence(),
            'lat' => fake()->optional()->latitude(),
            'lng' => fake()->optional()->longitude(),
        ];
    }
}
