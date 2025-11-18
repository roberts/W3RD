<?php

namespace App\Services\Agents;

use App\Models\Auth\Agent;
use App\Models\Auth\User;
use App\Models\Game\Game;
use Illuminate\Support\Facades\Log;

/**
 * AgentSchedulingService
 *
 * Responsible for finding available agent users for matchmaking.
 * Filters agents based on game compatibility, availability schedule,
 * and current activity status.
 */
class AgentSchedulingService
{
    /**
     * Find an available agent for the specified game title.
     *
     * @param string $gameSlug The game title slug (e.g., 'checkers', 'hearts')
     * @param string|null $mode Optional game mode for mode-specific agent selection
     * @return User|null Agent user if found, null otherwise
     */
    public function findAvailableAgent(string $gameSlug, ?string $mode = null): ?User
    {
        Log::debug('AgentSchedulingService searching for agent', [
            'game_slug' => $gameSlug,
            'mode' => $mode,
        ]);

        // Get agents that support this game title
        $agents = Agent::query()
            ->with('user') // Eager load the user relationship
            ->get()
            ->filter(function (Agent $agent) use ($gameSlug) {
                // Check if agent supports this game
                if ($agent->supported_game_titles === ['all']) {
                    return true;
                }
                if (is_array($agent->supported_game_titles) && in_array($gameSlug, $agent->supported_game_titles)) {
                    return true;
                }
                return false;
            })
            ->filter(function (Agent $agent) {
                // Filter by availability schedule
                return $agent->isAvailableNow();
            })
            ->filter(function (Agent $agent) {
                // Must have an associated user
                return $agent->user !== null;
            })
            ->filter(function (Agent $agent) {
                // Filter out agents currently in active games
                return ! $this->isAgentBusy($agent->user);
            })
            ->sortByDesc('difficulty'); // Prefer higher difficulty agents for variety
        
        $agent = $agents->first();

        if (!$agent) {
            Log::info('No available agent found for game', [
                'game_slug' => $gameSlug,
                'mode' => $mode,
            ]);
            return null;
        }

        Log::info('Agent found for matchmaking', [
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'user_id' => $agent->user->id,
            'difficulty' => $agent->difficulty,
        ]);

        return $agent->user;
    }

    /**
     * Check if an agent user is currently busy in an active game.
     *
     * @param User $user The agent user to check
     * @return bool True if agent is currently playing
     */
    protected function isAgentBusy(User $user): bool
    {
        // Check if user has any active games (not finished)
        return $user->players()
            ->whereHas('game', function ($query) {
                $query->whereIn('status', ['waiting', 'active', 'paused']);
            })
            ->exists();
    }

    /**
     * Get the effective difficulty for an agent in a specific game mode.
     *
     * @param Agent $agent
     * @param string $gameSlug
     * @param string|null $mode
     * @return int The difficulty level (1-10)
     */
    public function getEffectiveDifficulty(Agent $agent, string $gameSlug, ?string $mode = null): int
    {
        // Check for mode-specific difficulty override
        if ($mode && $agent->configuration) {
            $modeKey = "{$mode}_difficulty";
            
            if (isset($agent->configuration[$gameSlug][$modeKey])) {
                $modeDifficulty = $agent->configuration[$gameSlug][$modeKey];
                
                // Ensure it's within valid range
                return max(1, min(10, $modeDifficulty));
            }
        }

        // Return base difficulty
        return $agent->difficulty;
    }

    /**
     * Get count of available agents for a game title.
     *
     * @param string $gameSlug
     * @return int Count of available agents
     */
    public function getAvailableAgentCount(string $gameSlug): int
    {
        return Agent::query()
            ->where(function ($query) use ($gameSlug) {
                $query->where('supported_game_titles', 'all')
                    ->orWhereJsonContains('supported_game_titles', $gameSlug);
            })
            ->whereHas('user')
            ->get()
            ->filter(fn (Agent $agent) => $agent->isAvailableNow())
            ->filter(fn (Agent $agent) => ! $this->isAgentBusy($agent->user))
            ->count();
    }
}
