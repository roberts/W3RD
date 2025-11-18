<?php

namespace App\Providers;

use App\Games\BaseGameTitle;
use App\Models\Game\Game;
use Illuminate\Support\ServiceProvider;

class GameServiceProvider extends ServiceProvider
{
    public static function getMode(Game $game): BaseGameTitle
    {
        $map = [
            'checkers' => [
                'standard' => \App\Games\Checkers\Modes\StandardMode::class,
            ],
            'hearts' => [
                'standard' => \App\Games\Hearts\Modes\StandardMode::class,
            ],
            'validate-four' => [
                'standard' => \App\Games\ValidateFour\Modes\StandardMode::class,
                'pop-out' => \App\Games\ValidateFour\Modes\PopOutMode::class,
                'five' => \App\Games\ValidateFour\Modes\FiveMode::class,
                'eight-by-seven' => \App\Games\ValidateFour\Modes\EightBySevenMode::class,
                'nine-by-six' => \App\Games\ValidateFour\Modes\NineBySixMode::class,
            ],
        ];

        $gameTitleSlug = $game->title_slug->value;
        $gameModeSlug = $game->mode->slug;

        if (! isset($map[$gameTitleSlug][$gameModeSlug])) {
            throw new \Exception("Game mode not found for {$gameTitleSlug} and {$gameModeSlug}");
        }

        $class = $map[$gameTitleSlug][$gameModeSlug];

        return new $class($game);
    }
}
