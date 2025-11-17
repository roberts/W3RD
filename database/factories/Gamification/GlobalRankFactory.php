<?php

namespace Database\Factories\Gamification;

use App\Models\Auth\User;
use App\Models\Gamification\GlobalRank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gamification\GlobalRank>
 */
class GlobalRankFactory extends Factory
{
    protected $model = GlobalRank::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'total_points' => fake()->numberBetween(0, 10000),
            'rank' => fake()->numberBetween(1, 10000),
        ];
    }

    /**
     * Create a top-ranked user.
     */
    public function topRank(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_points' => fake()->numberBetween(5000, 10000),
            'rank' => fake()->numberBetween(1, 10),
        ]);
    }

    /**
     * Create a beginner rank.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_points' => fake()->numberBetween(0, 500),
            'rank' => fake()->numberBetween(5000, 10000),
        ]);
    }
}
