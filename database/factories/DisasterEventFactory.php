<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Region;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DisasterEvent>
 */
class DisasterEventFactory extends Factory
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
            'severity' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'event_type' => fake()->randomElement(['earthquake', 'hurricane', 'flood', 'wildfire', 'volcano', 'drought', 'tsunami', 'storm', 'other']),
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),
            'radius_km' => fake()->randomFloat(2, 1, 100),
            'country_id' => Country::factory(),
            'region_id' => Region::factory(),
            'city_id' => City::factory(),
            'affected_areas' => fake()->words(3),
            'event_date' => fake()->date(),
            'start_time' => fake()->dateTime(),
            'end_time' => fake()->dateTime(),
            'is_active' => true,
            'impact_assessment' => fake()->words(3),
            'travel_recommendations' => fake()->words(2),
            'official_sources' => fake()->words(2),
            'media_coverage' => fake()->paragraph(),
            'tourism_impact' => fake()->words(2),
            'external_sources' => fake()->words(2),
            'ai_summary' => fake()->paragraph(),
            'ai_recommendations' => fake()->paragraph(),
            'crisis_communication' => fake()->paragraph(),
            'keywords' => fake()->words(3),
            'magnitude' => fake()->randomFloat(2, 1, 10),
            'casualties' => fake()->numberBetween(0, 1000),
            'economic_impact' => fake()->sentence(),
            'infrastructure_damage' => fake()->sentence(),
            'emergency_response' => fake()->sentence(),
            'recovery_status' => fake()->word(),
            'processing_status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
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
     * Indicate that the event is high severity.
     */
    public function highSeverity(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'high',
        ]);
    }

    /**
     * Indicate that the event is critical severity.
     */
    public function criticalSeverity(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }
}
