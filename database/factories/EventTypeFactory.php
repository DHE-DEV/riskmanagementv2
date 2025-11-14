<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventType>
 */
class EventTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->word();

        return [
            'code' => $code,
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement([
                'fa-exclamation-triangle',
                'fa-fire',
                'fa-tint',
                'fa-wind',
                'fa-bolt',
                'fa-mountain',
                'fa-tree',
                'fa-globe',
            ]),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the event type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create earthquake event type.
     */
    public function earthquake(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'earthquake',
            'name' => 'Erdbeben',
            'icon' => 'fa-house-crack',
            'color' => '#DC2626',
        ]);
    }

    /**
     * Create flood event type.
     */
    public function flood(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'flood',
            'name' => 'Überschwemmung',
            'icon' => 'fa-water',
            'color' => '#2563EB',
        ]);
    }

    /**
     * Create exercise event type.
     */
    public function exercise(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'exercise',
            'name' => 'Übung',
            'icon' => 'fa-graduation-cap',
            'color' => '#059669',
        ]);
    }
}
