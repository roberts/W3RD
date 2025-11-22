<?php

namespace App\Jobs;

use App\GameEngine\Timer\TimerExpiredHandler;
use App\Models\Game\Game;
use App\Providers\GameServiceProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TimerExpiredJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $gameId,
        public int $playerId,
        public int $turnNumber,
    ) {}

    public function handle(TimerExpiredHandler $timerHandler): void
    {
        $game = Game::find($this->gameId);

        if (! $game) {
            return;
        }

        // Only apply timeout if the game is still on the same turn
        if (($game->game_state['turn_number'] ?? null) === $this->turnNumber) {
            $mode = GameServiceProvider::getMode($game);
            $stateClass = $mode->getStateClass();
            $gameState = $stateClass::fromArray($game->game_state ?? []);

            $timerHandler->checkAndHandle($game, $mode, $gameState);
        }
    }
}
