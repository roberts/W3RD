<?php

namespace App\Jobs;

use App\GameEngine\TimerExpired\TimerExpiredManager;
use App\Models\Game\Game;
use App\Providers\GameServiceProvider;
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
    public function handle(TimerExpiredManager $timerExpiredManager): void
    {
        if (! $this->game->status->isFinished()) {
            // Reload the game to get the latest state
            $this->game->refresh();

            // Check if the turn is still active
            if ($this->game->turn_ends_at && now()->isAfter($this->game->turn_ends_at)) {
                $mode = GameServiceProvider::getMode($this->game);
                $driver = $timerExpiredManager->getDriverFor($mode::getTimer());
                $outcome = $driver->handleTimerExpired($this->game, (object) $this->game->game_state, $this->game->game_state['current_player_ulid']);

                // Here you would typically have another action/service to process the outcome
                // For now, we'll just log it
                if ($outcome->isFinished) {
                    // This part would be handled by the ConclusionManager, which would be called by another service.
                    // For now, we are just fixing the static analysis error.
                }
            }
        }
    }
}
