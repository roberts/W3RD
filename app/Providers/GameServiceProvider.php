<?php

namespace App\Providers;

use App\Enums\GameAttributes\GamePacing;
use App\Enums\GameAttributes\GameSequence;
use App\Enums\GameAttributes\GameVisibility;
use App\GameEngine\Drivers\Pacing\AsynchronousTurnDriver;
use App\GameEngine\Drivers\Pacing\NullPacingDriver;
use App\GameEngine\Drivers\Pacing\RealtimeDriver;
use App\GameEngine\Drivers\Pacing\SynchronousTurnDriver;
use App\GameEngine\Drivers\Pacing\TickBasedDriver;
use App\GameEngine\Drivers\Sequence\InterleavedDriver;
use App\GameEngine\Drivers\Sequence\PhaseBasedDriver;
use App\GameEngine\Drivers\Sequence\SequentialDriver;
use App\GameEngine\Drivers\Sequence\SimultaneousDriver;
use App\GameEngine\Drivers\Visibility\AsymmetricInfoDriver;
use App\GameEngine\Drivers\Visibility\FogOfWarDriver;
use App\GameEngine\Drivers\Visibility\HiddenInformationDriver;
use App\GameEngine\Drivers\Visibility\PerfectInformationDriver;
use App\GameEngine\Handlers\PlacePieceHandler;
use App\GameEngine\Interfaces\PacingDriver;
use App\GameEngine\Interfaces\SequenceDriver;
use App\GameEngine\Interfaces\VisibilityDriver;
use App\GameEngine\TimerExpired\TimerExpiredManager;
use App\Games\BaseGameTitle;
use App\Models\Game\Game;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class GameServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register driver bindings
        $this->registerSequenceDrivers();
        $this->registerPacingDrivers();
        $this->registerVisibilityDrivers();
        $this->registerActionHandlers();

        // Register the TimerExpiredManager as singleton
        $this->app->singleton(TimerExpiredManager::class, function (Application $app) {
            return new TimerExpiredManager($app);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register sequence driver bindings.
     */
    protected function registerSequenceDrivers(): void
    {
        // Bind specific drivers
        $this->app->bind(SequentialDriver::class);
        $this->app->bind(SimultaneousDriver::class);
        $this->app->bind(PhaseBasedDriver::class);
        $this->app->bind(InterleavedDriver::class);

        // Contextual binding for SequenceDriver based on enum
        $this->app->bind(SequenceDriver::class, function (Application $app) {
            // This will be resolved with parameters in BaseGameTitle
            return $app->make(SequentialDriver::class);
        });
    }

    /**
     * Register pacing driver bindings.
     */
    protected function registerPacingDrivers(): void
    {
        // Bind specific drivers
        $this->app->bind(SynchronousTurnDriver::class);
        $this->app->bind(AsynchronousTurnDriver::class);
        $this->app->bind(RealtimeDriver::class);
        $this->app->bind(TickBasedDriver::class);
        $this->app->bind(NullPacingDriver::class);

        // Contextual binding for PacingDriver
        $this->app->bind(PacingDriver::class, function (Application $app) {
            return $app->make(SynchronousTurnDriver::class);
        });
    }

    /**
     * Register visibility driver bindings.
     */
    protected function registerVisibilityDrivers(): void
    {
        // Bind specific drivers
        $this->app->bind(PerfectInformationDriver::class);
        $this->app->bind(HiddenInformationDriver::class);
        $this->app->bind(FogOfWarDriver::class);
        $this->app->bind(AsymmetricInfoDriver::class);

        // Contextual binding for VisibilityDriver
        $this->app->bind(VisibilityDriver::class, function (Application $app) {
            return $app->make(PerfectInformationDriver::class);
        });
    }

    /**
     * Register action handler bindings.
     */
    protected function registerActionHandlers(): void
    {
        // Action handlers can have dependencies injected
        $this->app->bind(PlacePieceHandler::class, function (Application $app, array $parameters) {
            $rules = $parameters['rules'] ?? [];

            return new PlacePieceHandler($rules);
        });

        // Tag all action handlers for easy discovery
        $this->app->tag([
            PlacePieceHandler::class,
        ], 'game.action.handlers');
    }

    /**
     * Create a driver instance for a given GameSequence enum.
     */
    public static function makeSequenceDriver(GameSequence $sequence): SequenceDriver
    {
        return match ($sequence) {
            GameSequence::SEQUENTIAL => app(SequentialDriver::class),
            GameSequence::SIMULTANEOUS => app(SimultaneousDriver::class),
            GameSequence::PHASE_BASED => app(PhaseBasedDriver::class),
            GameSequence::INTERLEAVED => app(InterleavedDriver::class),
        };
    }

    /**
     * Create a driver instance for a given GamePacing enum.
     */
    public static function makePacingDriver(GamePacing $pacing): PacingDriver
    {
        return match ($pacing) {
            GamePacing::TURN_BASED_SYNC => app(SynchronousTurnDriver::class),
            GamePacing::TURN_BASED_ASYNC => app(AsynchronousTurnDriver::class),
            GamePacing::REALTIME => app(RealtimeDriver::class),
            GamePacing::TICK_BASED => app(TickBasedDriver::class),
        };
    }

    /**
     * Create a driver instance for a given GameVisibility enum.
     */
    public static function makeVisibilityDriver(GameVisibility $visibility): VisibilityDriver
    {
        return match ($visibility) {
            GameVisibility::PERFECT_INFORMATION => app(PerfectInformationDriver::class),
            GameVisibility::HIDDEN_INFORMATION => app(HiddenInformationDriver::class),
            GameVisibility::FOG_OF_WAR => app(FogOfWarDriver::class),
            GameVisibility::ASYMMETRIC_INFO => app(AsymmetricInfoDriver::class),
        };
    }

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

        return new $class(
            $game,
        );
    }
}
