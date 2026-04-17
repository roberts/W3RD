<?php

declare(strict_types=1);

namespace App\GameEngine;

use App\Exceptions\GameModeNotFoundException;
use App\GameTitles\BaseGameTitle;
use App\GameTitles\Checkers\Modes\StandardMode;
use App\GameTitles\ConnectFour\Modes\EightBySevenMode;
use App\GameTitles\ConnectFour\Modes\FiveMode;
use App\GameTitles\ConnectFour\Modes\NineBySixMode;
use App\GameTitles\ConnectFour\Modes\PopOutMode;
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
     *
     * @var array<string, array<string, class-string>>
     */
    private array $modes = [
        'checkers' => [
            'standard' => StandardMode::class,
        ],
        'hearts' => [
            'standard' => \App\GameTitles\Hearts\Modes\StandardMode::class,
        ],
        'connect-four' => [
            'standard' => \App\GameTitles\ConnectFour\Modes\StandardMode::class,
            'pop-out' => PopOutMode::class,
            'five' => FiveMode::class,
            'eight-by-seven' => EightBySevenMode::class,
            'nine-by-six' => NineBySixMode::class,
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
