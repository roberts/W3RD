<?php

namespace Database\Factories\Billing;

use App\Enums\GameTitle;
use App\Models\Auth\User;
use App\Models\Billing\Quota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Billing\Quota>
 */
class QuotaFactory extends Factory
{
    protected $model = Quota::class;

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
            'games_started' => fake()->numberBetween(0, 50),
            'reset_month' => now()->startOfMonth(),
        ];
    }

    /**
     * Create a quota at limit.
     */
    public function atLimit(): static
    {
        return $this->state(fn (array $attributes) => [
            'games_started' => 50,
        ]);
    }

    /**
     * Create a quota with no games played.
     */
    public function unused(): static
    {
        return $this->state(fn (array $attributes) => [
            'games_started' => 0,
        ]);
    }

    /**
     * Create a quota for Connect Four.
     */
    public function connectFour(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_slug' => GameTitle::CONNECT_FOUR->value,
        ]);
    }
}
