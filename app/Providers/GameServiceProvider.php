<?php

namespace App\Providers;

use App\Games\BaseGameTitle;
use App\Games\ValidateFour\Modes\PopOutMode;
use App\Games\ValidateFour\Modes\StandardMode;
use App\Models\Game\Game;
use Illuminate\Support\ServiceProvider;

class GameServiceProvider extends ServiceProvider
{
    public static function getMode(Game $game): BaseGameTitle
    {
        $map = [
            'validate-four' => [
                'standard' => StandardMode::class,
                'pop-out' => PopOutMode::class,
            ],
        ];

        $gameTitleSlug = $game->title_slug;
        $gameModeSlug = $game->mode->slug;

        if (! isset($map[$gameTitleSlug][$gameModeSlug])) {
            throw new \Exception("Game mode not found for {$gameTitleSlug} and {$gameModeSlug}");
        }

        $class = $map[$gameTitleSlug][$gameModeSlug];

        return new $class($game);
    }
}
