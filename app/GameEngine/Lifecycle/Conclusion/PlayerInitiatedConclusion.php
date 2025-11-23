<?php

declare(strict_types=1);

namespace App\GameEngine\Lifecycle\Conclusion;

use App\Enums\GameStatus;
use App\Enums\OutcomeType;
use App\Exceptions\GameAccessDeniedException;
use App\GameEngine\Events\GameCompleted;
use App\GameEngine\Events\GameStatusChanged;
use App\Models\Auth\User;
use App\Models\Games\Game;
use App\Models\Games\Player;

/**
 * Handles player-initiated game endings (concede/forfeit and abandon).
 */
class PlayerInitiatedConclusion
{
    /**
     * Process a player conceding/forfeiting the game.
     * The opponent is declared the winner.
     */
    public function processConcede(Game $game, User $concedingUser): void
    {
        // Find the opponent
        /** @var Player|null $opponent */
        $opponent = $game->players()
            ->where('user_id', '!=', $concedingUser->id)
            ->first();

        if (! $opponent) {
            throw new GameAccessDeniedException(
                'Cannot determine opponent for this game',
                $game->ulid,
                ['user_id' => $concedingUser->id]
            );
        }

        // Update game to completed with forfeit outcome
        $game->status = GameStatus::COMPLETED;
        $game->winner_id = $opponent->user_id;
        $game->outcome_type = OutcomeType::FORFEIT;
        $game->completed_at = now();
        $game->duration_seconds = (int) now()->diffInSeconds($game->started_at ?? $game->created_at);
        $game->save();

        // Broadcast events
        event(new GameStatusChanged($game));
        event(new GameCompleted(
            game: $game,
            winnerUlid: $opponent->ulid,
            isDraw: false
        ));
    }

    /**
     * Process a player abandoning the game.
     * No winner is declared, both players may be penalized.
     */
    public function processAbandon(Game $game): void
    {
        // Mark game as abandoned with no winner
        $game->status = GameStatus::ABANDONED;
        $game->winner_id = null;
        $game->outcome_type = OutcomeType::ABANDONED;
        $game->completed_at = now();
        $game->duration_seconds = (int) now()->diffInSeconds($game->started_at ?? $game->created_at);
        $game->save();

        // Broadcast status change
        event(new GameStatusChanged($game));
    }
}
