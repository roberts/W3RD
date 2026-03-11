<?php

namespace Database\Factories\Access;

use App\Enums\Platform;
use App\Models\Access\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'api_key' => Str::random(32),
            'website' => $this->faker->domainName,
            'platform' => $this->faker->randomElement(Platform::cases()),
            'is_active' => true,
            'use_trademarks' => false,
        ];
    }

    /**
     * Indicate that the client should have access to trademarked game titles.
     */
    public function withTrademarks(): static
    {
        return $this->state(fn (array $attributes) => [
            'use_trademarks' => true,
        ]);
    }
}
