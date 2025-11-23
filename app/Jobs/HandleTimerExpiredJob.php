<?php

namespace App\Jobs;

use App\GameEngine\ModeRegistry;
use App\GameEngine\Timer\TimerExpiredHandler;
use App\Models\Games\Game;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleTimerExpiredJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Game $game)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(TimerExpiredHandler $timerHandler, ModeRegistry $modeRegistry): void
    {
        if (! $this->game->status->isFinished()) {
            // Reload the game to get the latest state
            $this->game->refresh();

            $mode = $modeRegistry->resolve($this->game);
            $stateClass = $mode->getStateClass();
            $gameState = $stateClass::fromArray($this->game->game_state ?? []);

            // Check and handle timer expiration
            $timerHandler->checkAndHandle($this->game, $mode, $gameState);
        }
    }
}
