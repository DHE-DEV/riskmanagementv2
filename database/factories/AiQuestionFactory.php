<?php

namespace Database\Factories;

use App\Models\AiQuestion;
use App\Models\User;
use App\Support\AiProviders;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiQuestion>
 */
class AiQuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'area' => 'countries',
            'section' => 'basics',
            'provider' => AiProviders::OPENAI,
            'model' => 'gpt-5.4',
            'question' => fake()->sentence().'?',
            'status' => AiQuestion::STATUS_QUEUED,
        ];
    }

    public function answered(string $answer = 'Eine Antwort.'): static
    {
        return $this->state(fn () => [
            'status' => AiQuestion::STATUS_DONE,
            'answer' => $answer,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);
    }
}
