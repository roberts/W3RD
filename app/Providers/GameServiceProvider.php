<?php

namespace App\Providers;

use App\GameEngine\Actions\ActionMapper;
use App\GameEngine\Handlers\PlacePieceHandler;
use App\GameEngine\ModeRegistry;
use Illuminate\Support\ServiceProvider;

class GameServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the mode registry as a singleton
        $this->app->singleton(ModeRegistry::class);

        // Register the action mapper
        $this->app->singleton(ActionMapper::class);

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
}
