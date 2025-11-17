<?php

namespace Database\Factories\Game;

use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\RematchRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\RematchRequest>
 */
class RematchRequestFactory extends Factory
{
    protected $model = RematchRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'original_game_id' => Game::factory(),
            'requesting_user_id' => User::factory(),
            'opponent_user_id' => User::factory(),
            'new_game_id' => null,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5),
        ];
    }

    /**
     * Create an accepted rematch request.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'new_game_id' => Game::factory(),
        ]);
    }

    /**
     * Create a declined rematch request.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
        ]);
    }

    /**
     * Create an expired rematch request.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expires_at' => now()->subMinutes(10),
        ]);
    }
}
