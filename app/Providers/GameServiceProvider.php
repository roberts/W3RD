<?php

namespace App\Providers;

use App\GameEngine\Handlers\PlacePieceHandler;
use App\GameTitles\BaseGameTitle;
use App\Models\Games\Game;
use Illuminate\Support\ServiceProvider;

class GameServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register action handlers
        $this->registerActionHandlers();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register action handler bindings.
     */
    protected function registerActionHandlers(): void
    {
        // Action handlers can have dependencies injected
        $this->app->bind(PlacePieceHandler::class, function ($app, array $parameters) {
            $rules = $parameters['rules'] ?? [];

            return new PlacePieceHandler($rules);
        });

        // Tag all action handlers for easy discovery
        $this->app->tag([
            PlacePieceHandler::class,
        ], 'game.action.handlers');
    }

    public static function getMode(Game $game): BaseGameTitle
    {
        $map = [
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

        $gameTitleSlug = $game->title_slug->value;
        $gameModeSlug = $game->mode->slug;

        if (! isset($map[$gameTitleSlug][$gameModeSlug])) {
            throw new \Exception("Game mode not found for {$gameTitleSlug} and {$gameModeSlug}");
        }

        $class = $map[$gameTitleSlug][$gameModeSlug];

        return new $class(
            $game,
        );
    }
}
