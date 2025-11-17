<?php

namespace Database\Factories\Auth;

use App\Models\Auth\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Auth\Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Agent ' . fake()->firstName(),
            'description' => fake()->sentence(),
            'version' => fake()->semver(),
            'difficulty' => fake()->numberBetween(1, 10),
            'configuration' => [
                'aggressive' => fake()->boolean(),
                'defensive' => fake()->boolean(),
                'think_time_ms' => fake()->numberBetween(100, 5000),
            ],
            'ai_logic_path' => 'App\\AI\\' . fake()->word() . 'Strategy',
            'strategy_type' => fake()->randomElement(['minimax', 'mcts', 'neural', 'hybrid']),
            'supported_game_titles' => [fake()->randomElement(['validate-four', 'chess', 'checkers'])],
            'available_hour_est' => fake()->numberBetween(0, 23),
            'error_count' => 0,
            'last_error_at' => null,
            'debug_mode' => false,
        ];
    }

    /**
     * Indicate that the agent is in debug mode.
     */
    public function debugging(): static
    {
        return $this->state(fn (array $attributes) => [
            'debug_mode' => true,
        ]);
    }

    /**
     * Create a beginner difficulty agent.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => fake()->numberBetween(1, 3),
            'name' => 'Beginner Bot',
        ]);
    }

    /**
     * Create an expert difficulty agent.
     */
    public function expert(): static
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => fake()->numberBetween(8, 10),
            'name' => 'Expert Bot',
        ]);
    }
}
