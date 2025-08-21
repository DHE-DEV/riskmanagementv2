<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomEvent>
 */
class CustomEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'event_type' => fake()->randomElement(['earthquake', 'hurricane', 'flood', 'wildfire', 'volcano', 'drought', 'exercise', 'other']),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'marker_color' => fake()->randomElement(['#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF']),
            'marker_icon' => fake()->randomElement(['fa-map-marker', 'fa-exclamation-triangle', 'fa-fire', 'fa-tint']),
            'icon_color' => fake()->randomElement(['#FFFFFF', '#000000', '#FF0000']),
            'marker_size' => fake()->randomElement(['small', 'medium', 'large']),
            'popup_content' => fake()->paragraph(),
            'start_date' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+2 months'),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'category' => fake()->word(),
            'tags' => fake()->words(3),
            'is_active' => true,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the event is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the event is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the event is high severity.
     */
    public function highSeverity(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'high',
        ]);
    }
}
