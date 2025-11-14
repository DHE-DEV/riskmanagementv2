<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => fake()->boolean(80) ? now() : null,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'customer_type' => fake()->randomElement(['private', 'business']),
            'provider' => null,
            'provider_id' => null,
            'provider_token' => null,
            'provider_refresh_token' => null,
            'business_type' => null,
            'company_name' => null,
            'company_additional' => null,
            'company_street' => null,
            'company_house_number' => null,
            'company_postal_code' => null,
            'company_city' => null,
            'company_country' => null,
            'billing_company_name' => null,
            'billing_additional' => null,
            'billing_street' => null,
            'billing_house_number' => null,
            'billing_postal_code' => null,
            'billing_city' => null,
            'billing_country' => null,
            'passolution_access_token' => null,
            'passolution_token_expires_at' => null,
            'passolution_refresh_token' => null,
            'passolution_refresh_token_expires_at' => null,
            'passolution_subscription_type' => null,
            'passolution_features' => null,
            'passolution_subscription_updated_at' => null,
            'hide_profile_completion' => false,
            'directory_listing_active' => false,
            'branch_management_active' => false,
            'agent_id' => null,
            'service1_customer_id' => null,
            'phone' => null,
            'address' => null,
            'account_type' => null,
        ];
    }

    /**
     * Indicate that the customer is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the customer is a business customer.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'business',
            'business_type' => ['reisebuero', 'online'],
            'company_name' => fake()->company(),
            'company_additional' => fake()->optional()->secondaryAddress(),
            'company_street' => fake()->streetName(),
            'company_house_number' => fake()->buildingNumber(),
            'company_postal_code' => fake()->postcode(),
            'company_city' => fake()->city(),
            'company_country' => fake()->country(),
            'billing_company_name' => fake()->company(),
            'billing_street' => fake()->streetName(),
            'billing_house_number' => fake()->buildingNumber(),
            'billing_postal_code' => fake()->postcode(),
            'billing_city' => fake()->city(),
            'billing_country' => fake()->country(),
        ]);
    }

    /**
     * Indicate that the customer is a private customer.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'private',
            'business_type' => null,
            'company_name' => null,
            'company_additional' => null,
            'company_street' => null,
            'company_house_number' => null,
            'company_postal_code' => null,
            'company_city' => null,
            'company_country' => null,
        ]);
    }

    /**
     * Indicate that the customer uses social login.
     */
    public function socialLogin(string $provider = 'google'): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
            'provider_id' => fake()->uuid(),
            'provider_token' => Str::random(64),
            'provider_refresh_token' => Str::random(64),
            'password' => null,
        ]);
    }

    /**
     * Indicate that the customer has Passolution integration.
     */
    public function withPassolution(string $subscriptionType = 'premium'): static
    {
        return $this->state(fn (array $attributes) => [
            'passolution_access_token' => Str::random(64),
            'passolution_token_expires_at' => now()->addDays(7),
            'passolution_refresh_token' => Str::random(64),
            'passolution_refresh_token_expires_at' => now()->addDays(30),
            'passolution_subscription_type' => $subscriptionType,
            'passolution_features' => ['feature1', 'feature2', 'feature3'],
            'passolution_subscription_updated_at' => now(),
        ]);
    }

    /**
     * Indicate that the customer has branch management active.
     */
    public function withBranchManagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_management_active' => true,
        ]);
    }

    /**
     * Indicate that the customer has directory listing active.
     */
    public function withDirectoryListing(): static
    {
        return $this->state(fn (array $attributes) => [
            'directory_listing_active' => true,
        ]);
    }

    /**
     * Indicate that the customer has SSO fields.
     */
    public function withSSO(): static
    {
        return $this->state(fn (array $attributes) => [
            'agent_id' => fake()->uuid(),
            'service1_customer_id' => fake()->randomNumber(6),
            'phone' => fake()->phoneNumber(),
            'address' => [
                'street' => fake()->streetName(),
                'city' => fake()->city(),
                'postal_code' => fake()->postcode(),
                'country' => fake()->countryCode(),
            ],
            'account_type' => fake()->randomElement(['standard', 'premium', 'enterprise']),
        ]);
    }
}
