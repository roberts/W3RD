<?php

namespace App\Http\Traits;

use App\GameEngine\ModeRegistry;
use App\Models\Games\Game;

trait HydratesGameState
{
    /**
     * Hydrate game state from a Game model.
     */
    protected function hydrateGameState(Game $game): object
    {
        $mode = $this->modeRegistry->resolve($game);
        $stateClass = $mode->getStateClass();

        return $stateClass::fromArray($game->game_state ?? []);
    }
}
