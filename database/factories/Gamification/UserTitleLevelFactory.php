<?php

namespace Database\Factories\Gamification;

use App\Enums\GameTitle;
use App\Models\Auth\User;
use App\Models\Gamification\UserTitleLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gamification\UserTitleLevel>
 */
class UserTitleLevelFactory extends Factory
{
    protected $model = UserTitleLevel::class;

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
            'level' => fake()->numberBetween(1, 50),
            'xp_current' => fake()->numberBetween(0, 1000),
            'last_played_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create a beginner level.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => fake()->numberBetween(1, 5),
            'xp_current' => fake()->numberBetween(0, 100),
        ]);
    }

    /**
     * Create an expert level.
     */
    public function expert(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => fake()->numberBetween(40, 50),
            'xp_current' => fake()->numberBetween(800, 1000),
        ]);
    }

    /**
     * Create for Connect Four game.
     */
    public function connectFour(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_slug' => GameTitle::CONNECT_FOUR->value,
        ]);
    }
}
