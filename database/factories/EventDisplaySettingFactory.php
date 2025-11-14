<?php

namespace Database\Factories;

use App\Models\EventDisplaySetting;
use App\Models\EventType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventDisplaySetting>
 */
class EventDisplaySettingFactory extends Factory
{
    protected $model = EventDisplaySetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $strategies = ['default', 'manual_select', 'multi_event_type', 'show_all', 'show_icon_preview'];

        return [
            'multi_event_icon_strategy' => $this->faker->randomElement($strategies),
            'multi_event_type_id' => null,
            'show_icon_preview_in_form' => true,
            'strategy_description' => $this->faker->sentence(10),
        ];
    }

    /**
     * Indicate that the setting uses multi event type strategy.
     */
    public function withMultiEventType(): static
    {
        return $this->state(function (array $attributes) {
            // Try to get an existing EventType, or set to null if none exist
            $eventTypeId = EventType::query()->first()?->id;

            return [
                'multi_event_icon_strategy' => 'multi_event_type',
                'multi_event_type_id' => $eventTypeId,
            ];
        });
    }

    /**
     * Indicate that the setting uses default strategy.
     */
    public function defaultStrategy(): static
    {
        return $this->state(fn (array $attributes) => [
            'multi_event_icon_strategy' => 'default',
            'multi_event_type_id' => null,
        ]);
    }

    /**
     * Indicate that the setting uses manual select strategy.
     */
    public function manualSelectStrategy(): static
    {
        return $this->state(fn (array $attributes) => [
            'multi_event_icon_strategy' => 'manual_select',
            'multi_event_type_id' => null,
        ]);
    }

    /**
     * Indicate that the setting uses show all strategy.
     */
    public function showAllStrategy(): static
    {
        return $this->state(fn (array $attributes) => [
            'multi_event_icon_strategy' => 'show_all',
            'multi_event_type_id' => null,
        ]);
    }

    /**
     * Indicate that icon preview is disabled.
     */
    public function withoutIconPreview(): static
    {
        return $this->state(fn (array $attributes) => [
            'show_icon_preview_in_form' => false,
        ]);
    }
}
