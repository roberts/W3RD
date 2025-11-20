<?php

namespace Tests\Feature\Helpers;

use App\Enums\GameStatus;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Mode;
use Illuminate\Testing\TestResponse;

class GameHelper
{
    /**
     * Create a game with players.
     */
    public static function createGame(array $attributes = [], array $players = []): Game
    {
        // Always use ConnectFour Standard mode for consistent test behavior
        $mode = Mode::where('title_slug', 'connect-four')
            ->where('slug', 'standard')
            ->first() ?? Mode::factory()->connectFourStandard()->create();

        $baseAttributes = array_merge([
            'mode_id' => $mode->id,
            'status' => GameStatus::ACTIVE,
        ], $attributes);

        // If status is ACTIVE, ensure started_at is set
        if (($baseAttributes['status'] ?? null) === GameStatus::ACTIVE && ! isset($baseAttributes['started_at'])) {
            $baseAttributes['started_at'] = now();
        }

        // Create players if not provided
        if (empty($players)) {
            $game = Game::factory()->withPlayers(2)->create($baseAttributes);
        } else {
            // Extract users from players array
            $users = [];
            foreach ($players as $playerData) {
                $users[] = is_array($playerData) ? $playerData['user'] : $playerData;
            }
            $game = Game::factory()->withPlayers($users)->create($baseAttributes);
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
        $game = Game::factory()->completed()->withPlayers([$winner, $loser])->create([
            'creator_id' => $winner->id,
        ]);

        $game->update(['winner_id' => $game->players->first()->user_id]);

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
