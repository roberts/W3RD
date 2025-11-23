<?php

declare(strict_types=1);

namespace App\GameEngine\Lifecycle\Progression;

use App\Enums\GameStatus;
use App\GameEngine\Interfaces\GameTitleContract;
use App\Models\Auth\User;
use App\Models\Games\Game;
use App\Models\Games\Player;
use App\Agents\Orchestrators\AgentService;
use Illuminate\Support\Facades\Log;

/**
 * Coordinates agent actions in game progression.
 */
class AgentCoordinator
{
    public function __construct(
        protected AgentService $agentService
    ) {}

    /**
     * Trigger agent action if the current player is an agent.
     */
    public function triggerIfAgentTurn(Game $game, object $gameState, GameTitleContract $mode): void
    {
        // Skip if game is finished
        if ($game->status === GameStatus::COMPLETED) {
            return;
        }

        // Get the current player ULID from game state
        $currentPlayerUlid = $gameState->currentPlayerUlid ?? null;

        if (! $currentPlayerUlid) {
            return;
        }

        // Find the player record
        /** @var Player|null $player */
        $player = $game->players()->where('ulid', $currentPlayerUlid)->first();

        if (! $player) {
            return;
        }

        /** @var User|null $user */
        $user = $player->user;

        if (! $user) {
            return;
        }

        // Check if the player is an agent
        if ($user->isAgent()) {
            Log::debug('Next player is an agent, triggering action', [
                'game_id' => $game->id,
                'player_ulid' => $currentPlayerUlid,
                'agent_id' => $user->agent_id,
            ]);

            // Dispatch agent action via AgentService
            $this->agentService->performAction($user, $game);
        }
    }
}
