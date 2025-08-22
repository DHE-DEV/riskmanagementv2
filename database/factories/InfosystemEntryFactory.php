<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InfosystemEntry>
 */
class InfosystemEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $countries = [
            'DE' => ['de' => 'Deutschland', 'en' => 'Germany'],
            'FR' => ['de' => 'Frankreich', 'en' => 'France'],
            'IT' => ['de' => 'Italien', 'en' => 'Italy'],
            'ES' => ['de' => 'Spanien', 'en' => 'Spain'],
            'GB' => ['de' => 'Vereinigtes Königreich', 'en' => 'United Kingdom'],
            'US' => ['de' => 'Vereinigte Staaten', 'en' => 'United States'],
            'JP' => ['de' => 'Japan', 'en' => 'Japan'],
            'CN' => ['de' => 'China', 'en' => 'China'],
        ];

        $country = fake()->randomElement(array_keys($countries));
        $countryNames = $countries[$country];

        return [
            'api_id' => fake()->unique()->numberBetween(6500, 9999),
            'position' => fake()->numberBetween(0, 10),
            'appearance' => fake()->numberBetween(0, 5),
            'country_code' => $country,
            'country_names' => $countryNames,
            'lang' => 'de',
            'language_content' => 'German',
            'language_code' => 'de',
            'tagtype' => fake()->numberBetween(1, 5),
            'tagtext' => null,
            'tagdate' => fake()->dateTimeBetween('-30 days', '+7 days')->format('Y-m-d'),
            'header' => fake()->sentence(4),
            'content' => fake()->paragraph(3),
            'archive' => false,
            'active' => true,
            'api_created_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'request_id' => fake()->uuid(),
            'response_time' => fake()->numberBetween(50, 500),
        ];
    }

    /**
     * Indicate that the entry is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archive' => true,
        ]);
    }

    /**
     * Indicate that the entry is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the entry is in English.
     */
    public function english(): static
    {
        return $this->state(fn (array $attributes) => [
            'lang' => 'en',
            'language_content' => 'English',
            'language_code' => 'en',
        ]);
    }

    /**
     * Indicate that the entry is recent (today).
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'tagdate' => today()->format('Y-m-d'),
        ]);
    }

    /**
     * Set specific country
     */
    public function country(string $countryCode, array $countryNames): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => $countryCode,
            'country_names' => $countryNames,
        ]);
    }
}
