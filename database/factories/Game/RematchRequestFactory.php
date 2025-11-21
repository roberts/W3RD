<?php

namespace Database\Factories\Game;

use App\Models\Access\Client;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Player;
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
            'game_id' => null,
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
            'game_id' => Game::factory(),
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

    /**
     * Create a cancelled rematch request.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Create rematch request from a completed game with players.
     *
     * Creates: completed game -> 2 players -> rematch request
     *
     * @param  User|null  $requestingUser  Optional user who requests rematch (auto-created if null)
     * @param  User|null  $opponentUser  Optional opponent user (auto-created if null)
     * @param  int|null  $clientId  Optional client ID for players
     *
     * Returns the created rematch request with game, requesting_user, and opponent_user properties attached.
     *
     * Example:
     * ```php
     * // Auto-create everything
     * $rematch = RematchRequest::factory()->fromCompletedGame()->create()
     * $game = $rematch->game; // Access the completed game
     *
     * // With specific users
     * $rematch = RematchRequest::factory()->fromCompletedGame($user1, $user2)->create()
     * ```
     */
    public function fromCompletedGame(?User $requestingUser = null, ?User $opponentUser = null, ?int $clientId = null): static
    {
        return $this->state(function (array $attributes) use ($requestingUser, $opponentUser, $clientId) {
            // Create users if not provided
            $requestingUser = $requestingUser ?? User::factory()->create();
            $opponentUser = $opponentUser ?? User::factory()->create();

            // Create completed game
            $game = Game::factory()->completed()->create();

            Player::factory()->create([
                'game_id' => $game->getKey(),
                'user_id' => $requestingUser->getKey(),
                'position_id' => 1,
                'color' => 'red',
                'client_id' => $clientId ?? Client::factory()->withTrademarks(),
            ]);

            Player::factory()->create([
                'game_id' => $game->getKey(),
                'user_id' => $opponentUser->getKey(),
                'position_id' => 2,
                'color' => 'yellow',
                'client_id' => $clientId ?? Client::factory()->withTrademarks(),
            ]);

            return [
                'original_game_id' => $game->getKey(),
                'requesting_user_id' => $requestingUser->getKey(),
                'opponent_user_id' => $opponentUser->getKey(),
            ];
        })->afterCreating(function (RematchRequest $rematchRequest) {
            // Attach for easy access in tests
            $rematchRequest->game = $rematchRequest->originalGame; // @phpstan-ignore-line
            $rematchRequest->requesting_user = $rematchRequest->requestingUser; // @phpstan-ignore-line
            $rematchRequest->opponent_user = $rematchRequest->opponentUser; // @phpstan-ignore-line
        });
    }
}
