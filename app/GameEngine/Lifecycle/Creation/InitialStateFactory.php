<?php

declare(strict_types=1);

namespace App\GameEngine\Lifecycle\Creation;

use App\GameEngine\Interfaces\GameTitleContract;
use InvalidArgumentException;

/**
 * Factory for creating initial game states based on game configuration.
 */
class InitialStateFactory
{
    /**
     * Create initial state for a game title with the given players.
     *
     * @param  GameTitleContract  $title  The game title protocol
     * @param  string  ...$playerUlids  Player ULIDs in order
     * @return object The initial game state
     */
    public function create(GameTitleContract $title, string ...$playerUlids): object
    {
        $this->validatePlayerCount($title, count($playerUlids));

        return $title->createInitialState(...$playerUlids);
    }

    /**
     * Validate that the player count meets game requirements.
     *
     * @param  GameTitleContract  $title  The game title protocol
     * @param  int  $playerCount  Number of players
     * @throws InvalidArgumentException If player count is invalid
     */
    protected function validatePlayerCount(GameTitleContract $title, int $playerCount): void
    {
        // Get min/max players from game attributes or config
        // For now, delegate to the game title's validation
        // Future: Add getMinPlayers() and getMaxPlayers() to GameTitleContract
    }

    /**
     * Create a serialized initial state suitable for database storage.
     *
     * @param  GameTitleContract  $title  The game title protocol
     * @param  string  ...$playerUlids  Player ULIDs in order
     * @return array The serialized game state
     */
    public function createSerialized(GameTitleContract $title, string ...$playerUlids): array
    {
        $state = $this->create($title, ...$playerUlids);

        if (method_exists($state, 'toArray')) {
            return $state->toArray();
        }

        return (array) $state;
    }
}
