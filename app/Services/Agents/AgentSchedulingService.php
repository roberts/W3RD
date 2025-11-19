<?php

namespace App\Services\Agents;

use App\Models\Auth\Agent;
use App\Models\Auth\User;
use App\Models\Game\Game;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

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
     * @param  string  $gameSlug  The game title slug (e.g., 'checkers', 'hearts')
     * @param  string|null  $mode  Optional game mode for mode-specific agent selection
     * @param  int|null  $humanUserId  Optional human user ID to avoid recent opponents
     * @return User|null Agent user if found, null otherwise
     */
    public function findAvailableAgent(string $gameSlug, ?string $mode = null, ?int $humanUserId = null): ?User
    {
        Log::debug('AgentSchedulingService searching for agent', [
            'game_slug' => $gameSlug,
            'mode' => $mode,
            'human_user_id' => $humanUserId,
        ]);

        $currentHourEst = now('America/New_York')->hour;

        // Get recent opponents if humanUserId is provided
        $recentOpponents = [];
        if ($humanUserId) {
            $recentOpponents = Redis::lrange("recent_opponents:{$humanUserId}", 0, 2); // Last 3 opponents (0-2)
            $recentOpponents = array_map('intval', $recentOpponents); // Convert to integers
        }

        // Get all agents that support this game title
        $allAgents = Agent::query()
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
                // Must have an associated user
                return $agent->user !== null;
            })
            ->filter(function (Agent $agent) {
                // Filter out agents in cooldown period
                $cooldownKey = "agent:{$agent->user->id}:cooldown";

                return ! Redis::exists($cooldownKey);
            })
            ->filter(function (Agent $agent) {
                // Filter out agents currently in active games
                return ! $this->isAgentBusy($agent->user);
            })
            ->filter(function (Agent $agent) use ($recentOpponents) {
                // Filter out recent opponents if provided
                if (empty($recentOpponents)) {
                    return true;
                }

                return ! in_array($agent->user->id, $recentOpponents);
            });

        // Separate agents into time-specific and 24/7 (null availability)
        $timeSpecificAgents = $allAgents
            ->filter(function (Agent $agent) use ($currentHourEst) {
                // Only agents with a specific time slot that matches current hour
                return $agent->available_hour_est !== null && $agent->available_hour_est === $currentHourEst;
            })
            ->sortByDesc('difficulty');

        $agent247 = $allAgents
            ->filter(function (Agent $agent) {
                // Only agents with null availability (24/7)
                return $agent->available_hour_est === null;
            })
            ->sortByDesc('difficulty');

        // Prefer time-specific agents over 24/7 agents
        $agent = $timeSpecificAgents->first() ?? $agent247->first();

        if (! $agent) {
            // If no agent found and we filtered by recent opponents, try again without that filter (lenient fallback)
            if (! empty($recentOpponents)) {
                Log::info('No agent found excluding recent opponents, trying without filter', [
                    'game_slug' => $gameSlug,
                    'mode' => $mode,
                    'human_user_id' => $humanUserId,
                    'recent_opponents' => $recentOpponents,
                ]);

                return $this->findAvailableAgent($gameSlug, $mode, null);
            }

            Log::info('No available agent found for game', [
                'game_slug' => $gameSlug,
                'mode' => $mode,
                'current_hour_est' => $currentHourEst,
            ]);

            return null;
        }

        Log::info('Agent found for matchmaking', [
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'user_id' => $agent->user->id,
            'difficulty' => $agent->difficulty,
            'availability_type' => $agent->available_hour_est === null ? '24/7' : 'time-specific',
            'available_hour_est' => $agent->available_hour_est,
            'current_hour_est' => $currentHourEst,
            'excluded_recent_opponents' => ! empty($recentOpponents),
        ]);

        return $agent->user;
    }

    /**
     * Check if an agent user is currently busy in an active game.
     *
     * @param  User  $user  The agent user to check
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
     * @return int Count of available agents
     */
    public function getAvailableAgentCount(string $gameSlug): int
    {
        $currentHourEst = now('America/New_York')->hour;

        return Agent::query()
            ->with('user')
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
                // Must have an associated user
                return $agent->user !== null;
            })
            ->filter(function (Agent $agent) {
                // Filter out agents currently in active games
                return ! $this->isAgentBusy($agent->user);
            })
            ->filter(function (Agent $agent) use ($currentHourEst) {
                // Include agents available at current hour OR 24/7 agents
                return $agent->available_hour_est === null || $agent->available_hour_est === $currentHourEst;
            })
            ->count();
    }
}
