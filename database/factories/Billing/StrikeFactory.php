<?php

namespace Database\Factories\Billing;

use App\Enums\GameTitle;
use App\Models\Auth\User;
use App\Models\Billing\Strike;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Billing\Strike>
 */
class StrikeFactory extends Factory
{
    protected $model = Strike::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title_slug' => fake()->randomElement(GameTitle::cases())->value,
            'strikes_used' => fake()->numberBetween(0, 3),
            'strike_date' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create a strike with maximum strikes.
     */
    public function maxStrikes(): static
    {
        return $this->state(fn (array $attributes) => [
            'strikes_used' => 3,
        ]);
    }

    /**
     * Create a strike with no strikes used.
     */
    public function noStrikes(): static
    {
        return $this->state(fn (array $attributes) => [
            'strikes_used' => 0,
        ]);
    }

    /**
     * Create a recent strike.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'strike_date' => now(),
        ]);
    }
}
