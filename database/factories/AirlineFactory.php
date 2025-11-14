<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Airline>
 */
class AirlineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $airlines = [
            ['name' => 'Lufthansa', 'iata' => 'LH', 'icao' => 'DLH'],
            ['name' => 'Air France', 'iata' => 'AF', 'icao' => 'AFR'],
            ['name' => 'British Airways', 'iata' => 'BA', 'icao' => 'BAW'],
            ['name' => 'KLM', 'iata' => 'KL', 'icao' => 'KLM'],
            ['name' => 'Emirates', 'iata' => 'EK', 'icao' => 'UAE'],
            ['name' => 'Qatar Airways', 'iata' => 'QR', 'icao' => 'QTR'],
            ['name' => 'Singapore Airlines', 'iata' => 'SQ', 'icao' => 'SIA'],
            ['name' => 'Turkish Airlines', 'iata' => 'TK', 'icao' => 'THY'],
            ['name' => 'Etihad Airways', 'iata' => 'EY', 'icao' => 'ETD'],
            ['name' => 'Swiss International', 'iata' => 'LX', 'icao' => 'SWR'],
        ];

        $airline = fake()->randomElement($airlines);

        return [
            'name' => $airline['name'],
            'iata_code' => $airline['iata'],
            'icao_code' => $airline['icao'],
            'home_country_id' => Country::factory(),
            'headquarters' => fake()->city(),
            'website' => 'https://www.' . strtolower(str_replace(' ', '', $airline['name'])) . '.com',
            'booking_url' => 'https://www.' . strtolower(str_replace(' ', '', $airline['name'])) . '.com/booking',
            'contact_info' => [
                'hotline' => fake()->phoneNumber(),
                'email' => 'contact@' . strtolower(str_replace(' ', '', $airline['name'])) . '.com',
                'chat_url' => 'https://www.' . strtolower(str_replace(' ', '', $airline['name'])) . '.com/chat',
                'help_url' => 'https://www.' . strtolower(str_replace(' ', '', $airline['name'])) . '.com/help',
            ],
            'baggage_rules' => [
                'checked_baggage' => [
                    'economy' => '1x23kg',
                    'premium_economy' => '2x23kg',
                    'business' => '2x32kg',
                    'first' => '3x32kg',
                ],
                'hand_baggage' => [
                    'economy' => '1x8kg',
                    'premium_economy' => '2x8kg',
                    'business' => '2x8kg',
                    'first' => '2x8kg',
                ],
                'hand_baggage_dimensions' => [
                    'economy' => [
                        'length' => 55,
                        'width' => 40,
                        'height' => 23,
                    ],
                    'premium_economy' => [
                        'length' => 55,
                        'width' => 40,
                        'height' => 23,
                    ],
                    'business' => [
                        'length' => 55,
                        'width' => 40,
                        'height' => 23,
                    ],
                    'first' => [
                        'length' => 55,
                        'width' => 40,
                        'height' => 23,
                    ],
                ],
            ],
            'cabin_classes' => ['economy', 'business'],
            'pet_policy' => [
                'allowed' => fake()->boolean(70),
                'in_cabin' => [
                    'max_weight' => '8kg',
                    'carrier_size' => '55x40x23cm',
                ],
                'in_hold' => [
                    'max_weight' => '75kg',
                    'notes' => 'Temperature-controlled cargo hold',
                ],
                'info_url' => 'https://www.' . strtolower(str_replace(' ', '', $airline['name'])) . '.com/pets',
                'notes' => 'Pets must be in approved carriers',
            ],
            'lounges' => [],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the airline is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the airline has lounges.
     */
    public function withLounges(): static
    {
        return $this->state(fn (array $attributes) => [
            'lounges' => [
                [
                    'name' => 'Business Lounge',
                    'location' => 'Frankfurt Terminal 1',
                    'access' => 'Business Class, HON Circle',
                    'url' => 'https://example.com/lounge1',
                ],
                [
                    'name' => 'First Class Lounge',
                    'location' => 'Munich Terminal 2',
                    'access' => 'First Class',
                    'url' => 'https://example.com/lounge2',
                ],
            ],
        ]);
    }

    /**
     * Indicate that the airline offers all cabin classes.
     */
    public function withAllCabinClasses(): static
    {
        return $this->state(fn (array $attributes) => [
            'cabin_classes' => ['economy', 'premium_economy', 'business', 'first'],
        ]);
    }
}
