<?php

declare(strict_types=1);

namespace App\Services\Games;

use App\GameEngine\ModeRegistry;
use App\Models\Games\Game;

class GameStateService
{
    public function __construct(
        protected ModeRegistry $modeRegistry
    ) {}

    /**
     * Hydrate game state from a Game model.
     */
    public function hydrateState(Game $game): object
    {
        $mode = $this->modeRegistry->resolve($game);
        $stateClass = $mode->getStateClass();

        return $stateClass::fromArray($game->game_state ?? []);
    }
}
