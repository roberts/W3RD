<?php

namespace App\Services\Agents;

use App\Exceptions\AgentConfigurationException;
use App\Interfaces\AgentContract;
use App\Jobs\CalculateAgentAction;
use App\Models\Auth\Agent;
use App\Models\Auth\User;
use App\Models\Game\Game;
use Illuminate\Support\Facades\Log;

/**
 * AgentService
 *
 * Responsible for orchestrating agent turns in games.
 * Dispatches background jobs to calculate and execute agent actions.
 */
class AgentService
{
    /**
     * Perform an agent's action in the specified game.
     *
     * This method dispatches a background job that will:
     * 1. Calculate the agent's next action
     * 2. Wait for a random delay (1-8 seconds) for human-like behavior
     * 3. Apply the action to the game
     *
     * @param  User  $user  The agent user
     * @param  Game  $game  The game instance
     *
     * @throws AgentConfigurationException If user is not an agent
     */
    public function performAction(User $user, Game $game): void
    {
        if (! $user->isAgent()) {
            throw new AgentConfigurationException(
                'User must be an agent to perform agent actions',
                null,
                ['user_id' => $user->id]
            );
        }

        Log::info('AgentService dispatching action calculation', [
            'user_id' => $user->id,
            'agent_id' => $user->agent_id,
            'game_id' => $game->id,
            'game_title' => $game->title_slug->value ?? 'unknown',
        ]);

        // Dispatch the background job to calculate and execute the action
        CalculateAgentAction::dispatch($user, $game);
    }

    /**
     * Get the AI logic instance for an agent.
     *
     * @param  User  $user  The agent user
     *
     * @throws AgentConfigurationException If logic class doesn't exist or doesn't implement AgentContract
     */
    public function getAgentLogic(User $user): AgentContract
    {
        if (! $user->isAgent()) {
            throw new AgentConfigurationException(
                'User must be an agent',
                null,
                ['user_id' => $user->id]
            );
        }

        /** @var Agent $agent */
        $agent = $user->agent;
        $logicClass = $agent->ai_logic_path;

        if (! class_exists($logicClass)) {
            Log::error('Agent logic class not found', [
                'agent_id' => $agent->id,
                'logic_class' => $logicClass,
            ]);
            throw new AgentConfigurationException(
                "Agent logic class not found: {$logicClass}",
                $logicClass,
                ['agent_id' => $agent->id]
            );
        }

        $logic = app($logicClass);

        if (! $logic instanceof AgentContract) {
            Log::error('Agent logic class does not implement AgentContract', [
                'agent_id' => $agent->id,
                'logic_class' => $logicClass,
            ]);
            throw new AgentConfigurationException(
                "Agent logic must implement AgentContract: {$logicClass}",
                $logicClass,
                ['agent_id' => $agent->id, 'required_interface' => AgentContract::class]
            );
        }

        return $logic;
    }
}
