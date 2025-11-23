<?php

declare(strict_types=1);

namespace App\GameEngine;

use App\Exceptions\GameModeNotFoundException;
use App\GameTitles\BaseGameTitle;
use App\Models\Games\Game;

/**
 * Registry for resolving game modes from game instances.
 *
 * Maps game title slugs and mode slugs to their concrete mode implementations.
 */
class ModeRegistry
{
    /**
     * Map of game titles to their available modes.
     */
    private array $modes = [
        'checkers' => [
            'standard' => \App\GameTitles\Checkers\Modes\StandardMode::class,
        ],
        'hearts' => [
            'standard' => \App\GameTitles\Hearts\Modes\StandardMode::class,
        ],
        'connect-four' => [
            'standard' => \App\GameTitles\ConnectFour\Modes\StandardMode::class,
            'pop-out' => \App\GameTitles\ConnectFour\Modes\PopOutMode::class,
            'five' => \App\GameTitles\ConnectFour\Modes\FiveMode::class,
            'eight-by-seven' => \App\GameTitles\ConnectFour\Modes\EightBySevenMode::class,
            'nine-by-six' => \App\GameTitles\ConnectFour\Modes\NineBySixMode::class,
        ],
    ];

    /**
     * Resolve the mode handler for a game.
     *
     * @throws GameModeNotFoundException
     */
    public function resolve(Game $game): BaseGameTitle
    {
        $gameTitleSlug = $game->title_slug->value;
        $gameModeSlug = $game->mode->slug;

        if (! isset($this->modes[$gameTitleSlug][$gameModeSlug])) {
            throw new GameModeNotFoundException(
                "Game mode not found for {$gameTitleSlug}:{$gameModeSlug}",
                $game->title_slug
            );
        }

        $class = $this->modes[$gameTitleSlug][$gameModeSlug];

        return new $class($game);
    }
}
