<?php

namespace App\Jobs;

use App\Actions\Game\HandleTimeoutAction;
use App\Models\Game\Game;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Providers\GameServiceProvider;

class TimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $gameId,
        public int $playerId,
        public int $turnNumber,
    ) {
    }

    public function handle(HandleTimeoutAction $handleTimeout): void
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
            
            $handleTimeout->execute($game, $mode, $gameState);
        }
    }
}
