<?php

namespace Database\Factories\Access;

use App\Enums\Platform;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Access\Client>
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
            'api_key' => \Illuminate\Support\Str::random(32),
            'platform' => $this->faker->randomElement(Platform::cases()),
            'is_active' => true,
        ];
    }
}
