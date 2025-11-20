<?php

namespace App\Providers;

use App\Enums\GameAttributes\GameVisibility;
use App\GameEngine\Interfaces\GameRedactor;
use App\GameEngine\Redactors\NullGameRedactor;
use App\Games\Hearts\HeartsRedactor;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class GameRedactorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GameRedactor::class, function (Application $app) {
            /** @var Request $request */
            $request = $app->make(Request::class);
            $game = $request->route('game');

            if (! $game) {
                // Default to NullGameRedactor if game is not in the route
                return new NullGameRedactor;
            }

            return match ($game->title->getVisibility()) {
                GameVisibility::HIDDEN_INFORMATION => new HeartsRedactor,
                default => new NullGameRedactor,
            };
        });
    }
}
