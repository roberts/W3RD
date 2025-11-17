<?php

namespace Tests\Feature\Traits;

use App\Enums\GameStatus;
use App\Models\Auth\User;
use App\Models\Game\Game;

trait CreatesGames
{
    /**
     * Create an active game with the given user as a player
     */
    protected function createActiveGame(User $user, string $title = 'validate-four'): Game
    {
        $game = Game::factory()->create([
            'title_slug' => $title,
            'status' => GameStatus::IN_PROGRESS,
            'current_turn' => 1,
        ]);

        $game->players()->attach($user->id, ['player_number' => 1]);

        return $game->fresh();
    }

    /**
     * Create a waiting game (not yet started)
     */
    protected function createWaitingGame(User $user, string $title = 'validate-four'): Game
    {
        $game = Game::factory()->create([
            'title_slug' => $title,
            'status' => GameStatus::WAITING,
        ]);

        $game->players()->attach($user->id, ['player_number' => 1]);

        return $game->fresh();
    }

    /**
     * Create a completed game
     */
    protected function createCompletedGame(User $winner, string $title = 'validate-four'): Game
    {
        $game = Game::factory()->create([
            'title_slug' => $title,
            'status' => GameStatus::COMPLETED,
            'winner_id' => $winner->id,
            'completed_at' => now(),
        ]);

        $game->players()->attach($winner->id, ['player_number' => 1]);

        return $game->fresh();
    }
}
