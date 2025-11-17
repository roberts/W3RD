<?php

namespace Database\Factories\Game;

use App\Enums\ActionType;
use App\Models\Game\Action;
use App\Models\Game\Game;
use App\Models\Game\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Action>
 */
class ActionFactory extends Factory
{
    protected $model = Action::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'player_id' => Player::factory(),
            'turn_number' => fake()->numberBetween(1, 50),
            'action_type' => fake()->randomElement(ActionType::cases()),
            'action_details' => [
                'column' => fake()->numberBetween(0, 6),
                'row' => fake()->optional()->numberBetween(0, 5),
            ],
            'status' => 'success',
            'error_code' => null,
            'timestamp_client' => now(),
        ];
    }

    /**
     * Create a drop piece action.
     */
    public function drop(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => ActionType::DROP_PIECE,
            'action_details' => [
                'column' => fake()->numberBetween(0, 6),
            ],
        ]);
    }

    /**
     * Create a failed action.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_code' => 'INVALID_MOVE',
        ]);
    }
}
