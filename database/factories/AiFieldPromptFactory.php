<?php

namespace Database\Factories;

use App\Models\AiFieldPrompt;
use App\Support\AiProviders;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiFieldPrompt>
 */
class AiFieldPromptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'area' => 'countries',
            'field' => 'risk_security_crime_level',
            'name' => fake()->words(3, true),
            'prompt' => 'Wie hoch ist die Kriminalität in {name}?',
            'provider' => AiProviders::OPENAI,
            'model' => 'gpt-5.4',
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
