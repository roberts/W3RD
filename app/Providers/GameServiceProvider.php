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
            'connect-four' => [
                'standard' => \App\Games\ConnectFour\Modes\StandardMode::class,
                'pop-out' => \App\Games\ConnectFour\Modes\PopOutMode::class,
                'five' => \App\Games\ConnectFour\Modes\FiveMode::class,
                'eight-by-seven' => \App\Games\ConnectFour\Modes\EightBySevenMode::class,
                'nine-by-six' => \App\Games\ConnectFour\Modes\NineBySixMode::class,
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
