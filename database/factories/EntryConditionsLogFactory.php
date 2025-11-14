<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EntryConditionsLog>
 */
class EntryConditionsLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $success = fake()->boolean(80);

        $filters = [
            'passport' => fake()->boolean(70),
            'idCard' => fake()->boolean(50),
            'tempPassport' => fake()->boolean(20),
            'tempIdCard' => fake()->boolean(15),
            'childPassport' => fake()->boolean(30),
            'visaFree' => fake()->boolean(60),
            'eVisa' => fake()->boolean(40),
            'visaOnArrival' => fake()->boolean(35),
            'noInsurance' => fake()->boolean(50),
            'noEntryForm' => fake()->boolean(45),
        ];

        $nationalities = ['DE', 'AT', 'CH', 'FR', 'IT', 'ES', 'GB', 'US', 'CA', 'AU'];

        $requestBody = [
            'nationality' => fake()->randomElement($nationalities),
            'destination' => fake()->countryCode(),
            'date' => fake()->date(),
        ];

        $responseData = $success ? [
            'success' => true,
            'data' => [
                'requirements' => [
                    'passport' => fake()->boolean(),
                    'visa' => fake()->boolean(),
                    'vaccination' => fake()->boolean(),
                ],
                'details' => fake()->sentence(),
            ],
            'count' => fake()->numberBetween(1, 10),
        ] : null;

        return [
            'filters' => $filters,
            'nationality' => fake()->randomElement($nationalities),
            'request_body' => $requestBody,
            'response_data' => $responseData,
            'response_status' => $success ? 200 : fake()->randomElement([400, 404, 500, 503]),
            'results_count' => $success ? fake()->numberBetween(1, 10) : 0,
            'success' => $success,
            'error_message' => $success ? null : fake()->sentence(),
        ];
    }

    /**
     * Indicate that the log was successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => true,
            'response_status' => 200,
            'results_count' => fake()->numberBetween(1, 10),
            'error_message' => null,
            'response_data' => [
                'success' => true,
                'data' => [
                    'requirements' => [
                        'passport' => true,
                        'visa' => false,
                    ],
                ],
                'count' => 5,
            ],
        ]);
    }

    /**
     * Indicate that the log failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => false,
            'response_status' => fake()->randomElement([400, 404, 500]),
            'results_count' => 0,
            'error_message' => 'API request failed: ' . fake()->sentence(),
            'response_data' => null,
        ]);
    }

    /**
     * Set specific nationality.
     */
    public function forNationality(string $nationality): static
    {
        return $this->state(fn (array $attributes) => [
            'nationality' => $nationality,
        ]);
    }
}
