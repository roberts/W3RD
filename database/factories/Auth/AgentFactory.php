<?php

namespace Database\Factories\Auth;

use App\Models\Auth\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'name' => 'Agent '.fake()->firstName(),
            'description' => fake()->sentence(),
            'version' => '1.0.0',
            'difficulty' => fake()->numberBetween(1, 10),
            'configuration' => null,
            'ai_logic_path' => 'App\\Agents\\Logic\\RandomLogic',
            'strategy_type' => fake()->randomElement(['aggressive', 'defensive', 'balanced', 'random']),
            'supported_game_titles' => ['checkers', 'hearts', 'validatefour'],
            'available_hour_est' => null, // Available 24/7 by default for testing
            'error_count' => 0,
            'last_error_at' => null,
            'debug_mode' => false,
        ];
    }

    /**
     * Agent that plays all games
     */
    public function allGames(): self
    {
        return $this->state(fn (array $attributes) => [
            'supported_game_titles' => ['all'],
        ]);
    }

    /**
     * Agent for a specific game
     */
    public function forGame(string $gameSlug): self
    {
        return $this->state(fn (array $attributes) => [
            'supported_game_titles' => [$gameSlug],
        ]);
    }

    /**
     * Agent with specific difficulty
     */
    public function withDifficulty(int $difficulty): self
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => max(1, min(10, $difficulty)),
        ]);
    }

    /**
     * Agent available 24/7
     */
    public function alwaysAvailable(): self
    {
        return $this->state(fn (array $attributes) => [
            'available_hour_est' => null,
        ]);
    }

    /**
     * Agent with specific AI logic
     */
    public function withLogic(string $logicClass): self
    {
        return $this->state(fn (array $attributes) => [
            'ai_logic_path' => $logicClass,
        ]);
    }

    /**
     * Agent with mode-specific configuration
     */
    public function withModeConfig(string $gameSlug, string $mode, int $difficulty): self
    {
        return $this->state(fn (array $attributes) => [
            'configuration' => [
                $gameSlug => [
                    $mode.'_difficulty' => $difficulty,
                ],
            ],
        ]);
    }
}
