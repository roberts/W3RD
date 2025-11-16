<?php

namespace App\Games;

use App\Interfaces\GameTitleContract;
use App\Models\Game\Game;

abstract class BaseGameTitle implements GameTitleContract
{
    protected Game $game;

    protected BaseGameState $gameState;

    public function __construct(Game $game)
    {
        $this->game = $game;

        $gameStateClass = $this->getGameStateClass();
        $this->gameState = $gameStateClass::fromArray($game->game_state);
    }

    /**
     * Returns the fully qualified class name of the game state object.
     *
     * @return string
     */
    abstract protected function getGameStateClass(): string;

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getGameState(): BaseGameState
    {
        return $this->gameState;
    }

    /**
     * Returns the structured rules for this game title.
     *
     * @return array
     */
    public static function getRules(): array
    {
        return [
            'title' => 'Game Title',
            'description' => 'Base description for a game.',
            'sections' => [],
        ];
    }
}
