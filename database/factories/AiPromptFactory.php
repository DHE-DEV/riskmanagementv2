<?php

namespace Database\Factories;

use App\Models\AiPrompt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiPrompt>
 */
class AiPromptFactory extends Factory
{
    protected $model = AiPrompt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $modelTypes = ['Country', 'Continent', 'Region', 'City', 'Airport', 'CustomEvent', 'PassolutionEvent', 'TextImprovement_Title', 'TextImprovement_Description'];
        $categories = ['Sicherheit', 'Wirtschaft', 'Politik', 'Gesundheit', 'Umwelt', 'Verkehr'];

        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(10),
            'model_type' => $this->faker->randomElement($modelTypes),
            'prompt_template' => $this->faker->paragraph(3) . "\n\nPlatzhalter: {name}, {description}, {iso_code}",
            'category' => $this->faker->randomElement($categories),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the prompt is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a prompt for Country model type.
     */
    public function forCountry(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'Country',
            'prompt_template' => "Erstelle eine detaillierte Risikobewertung für {name}.\n\nLänderdaten:\n- ISO-Code: {iso_code}\n- Kontinent: {continent}\n- EU-Mitglied: {is_eu_member}\n- Bevölkerung: {population}",
        ]);
    }

    /**
     * Create a prompt for CustomEvent model type.
     */
    public function forCustomEvent(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'CustomEvent',
            'prompt_template' => "Analysiere das Event {title}.\n\nEvent-Daten:\n- Beschreibung: {description}\n- Typ: {event_type}\n- Risiko-Level: {risk_level}\n- Start: {start_date}\n- Ende: {end_date}",
        ]);
    }

    /**
     * Create a prompt for TextImprovement_Title model type.
     */
    public function forTextImprovement(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_type' => 'TextImprovement_Title',
            'prompt_template' => "Verbessere folgenden Titel:\n\n{text}\n\nKontext:\n- Beschreibung: {description}\n- Ausgewählte Event-Typen: {selected_event_types}",
        ]);
    }
}
