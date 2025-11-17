<?php

namespace Tests\Feature\Helpers;

use App\Enums\GameStatus;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Mode;
use App\Models\Game\Player;
use Illuminate\Testing\TestResponse;

class GameHelper
{
    /**
     * Create a game with players.
     */
    public static function createGame(array $attributes = [], array $players = []): Game
    {
        $mode = Mode::first() ?? Mode::factory()->create();

        $game = Game::factory()->create(array_merge([
            'mode_id' => $mode->id,
            'status' => GameStatus::ACTIVE,
        ], $attributes));

        // Create players if not provided
        if (empty($players)) {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user1->id,
                'position_id' => 1,
            ]);

            Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user2->id,
                'position_id' => 2,
            ]);
        } else {
            foreach ($players as $index => $playerData) {
                // Support both User objects and arrays with 'user' and 'position_id' keys
                if (is_array($playerData)) {
                    Player::factory()->create([
                        'game_id' => $game->id,
                        'user_id' => $playerData['user']->id,
                        'position_id' => $playerData['position_id'] ?? ($index + 1),
                    ]);
                } else {
                    // Direct User object
                    Player::factory()->create([
                        'game_id' => $game->id,
                        'user_id' => $playerData->id,
                        'position_id' => $index + 1,
                    ]);
                }
            }
        }

        return $game->fresh(['players', 'mode']);
    }

    /**
     * Submit a game action.
     */
    public static function submitAction(Game $game, User $user, array $actionData): TestResponse
    {
        return test()->actingAs($user)->postJson("/api/v1/games/{$game->ulid}/action", $actionData);
    }

    /**
     * Assert game state matches expected values.
     */
    public static function assertGameState(Game $game, array $expectedState): void
    {
        $game->refresh();

        foreach ($expectedState as $key => $value) {
            expect($game->$key)->toBe($value);
        }
    }

    /**
     * Create a completed game.
     */
    public static function createCompletedGame(User $winner, User $loser): Game
    {
        $game = Game::factory()->completed()->create([
            'creator_id' => $winner->id,
        ]);

        $winnerPlayer = Player::factory()->create([
            'game_id' => $game->id,
            'user_id' => $winner->id,
            'position_id' => 1,
        ]);

        Player::factory()->create([
            'game_id' => $game->id,
            'user_id' => $loser->id,
            'position_id' => 2,
        ]);

        $game->update(['winner_id' => $winnerPlayer->id]);

        return $game->fresh(['players', 'mode']);
    }

    /**
     * Get available moves for a game.
     */
    public static function getAvailableMoves(Game $game, User $user): TestResponse
    {
        return test()->actingAs($user)->getJson("/api/v1/games/{$game->ulid}/options");
    }

    /**
     * Forfeit a game.
     */
    public static function forfeitGame(Game $game, User $user): TestResponse
    {
        return test()->actingAs($user)->postJson("/api/v1/games/{$game->ulid}/forfeit");
    }

    /**
     * Get game history.
     */
    public static function getGameHistory(Game $game, User $user): TestResponse
    {
        return test()->actingAs($user)->getJson("/api/v1/games/{$game->ulid}/history");
    }

    /**
     * Create a game with specific state.
     */
    public static function createGameWithState(array $gameState, array $attributes = []): Game
    {
        return self::createGame(array_merge([
            'game_state' => $gameState,
        ], $attributes));
    }
}
