<?php

namespace Database\Factories\Auth;

use App\Models\Auth\SocialAccount;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Auth\SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_name' => fake()->randomElement(['google', 'facebook', 'twitter', 'github', 'discord']),
            'provider_id' => fake()->unique()->numerify('##########'),
            'provider_token' => fake()->sha256(),
            'provider_refresh_token' => fake()->optional()->sha256(),
        ];
    }

    /**
     * Create a Google social account.
     */
    public function google(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_name' => 'google',
        ]);
    }

    /**
     * Create a Discord social account.
     */
    public function discord(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_name' => 'discord',
        ]);
    }

    /**
     * Create a GitHub social account.
     */
    public function github(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_name' => 'github',
        ]);
    }
}
