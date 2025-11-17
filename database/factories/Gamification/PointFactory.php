<?php

namespace Database\Factories\Gamification;

use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Gamification\Point;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gamification\Point>
 */
class PointFactory extends Factory
{
    protected $model = Point::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_type' => Game::class,
            'source_id' => fake()->randomNumber(),
            'change' => fake()->numberBetween(-50, 100),
            'new_total' => 0, // Will be calculated in model boot
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Create a point gain (positive change).
     */
    public function gain(): static
    {
        return $this->state(fn (array $attributes) => [
            'change' => fake()->numberBetween(10, 100),
            'description' => 'Points earned from ' . fake()->randomElement(['winning', 'completing', 'achieving']),
        ]);
    }

    /**
     * Create a point loss (negative change).
     */
    public function loss(): static
    {
        return $this->state(fn (array $attributes) => [
            'change' => fake()->numberBetween(-50, -5),
            'description' => 'Points deducted for ' . fake()->randomElement(['forfeiting', 'timeout', 'penalty']),
        ]);
    }

    /**
     * Create a large bonus.
     */
    public function bonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'change' => fake()->numberBetween(50, 200),
            'description' => 'Bonus points awarded',
        ]);
    }
}
