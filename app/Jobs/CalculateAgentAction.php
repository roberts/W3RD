<?php

namespace App\Jobs;

use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Services\Agents\AgentSchedulingService;
use App\Services\Agents\AgentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CalculateAgentAction Job
 *
 * Background job that calculates and applies an agent's action in a game.
 * Includes a random delay (1-8 seconds) to simulate human thinking time.
 */
class CalculateAgentAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public Game $game
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(AgentService $agentService, AgentSchedulingService $schedulingService): void
    {
        $startTime = microtime(true);

        try {
            Log::info('CalculateAgentAction job started', [
                'user_id' => $this->user->id,
                'agent_id' => $this->user->agent_id,
                'game_id' => $this->game->id,
            ]);

            // Verify user is still an agent
            if (! $this->user->isAgent()) {
                Log::error('User is no longer an agent', [
                    'user_id' => $this->user->id,
                    'game_id' => $this->game->id,
                ]);
                return;
            }

            /** @var \App\Models\Auth\Agent $agent */
            $agent = $this->user->agent;

            // Get the AI logic instance
            $logic = $agentService->getAgentLogic($this->user);

            // Determine effective difficulty for this game/mode
            $difficulty = $schedulingService->getEffectiveDifficulty(
                $agent,
                $this->game->title_slug->value ?? 'unknown',
                $this->game->mode->slug ?? null
            );

            // Calculate the next action
            $action = $logic->calculateNextAction($this->game, $difficulty);

            $calculationTime = microtime(true) - $startTime;

            Log::info('Agent action calculated', [
                'game_id' => $this->game->id,
                'calculation_time_ms' => round($calculationTime * 1000, 2),
                'difficulty' => $difficulty,
            ]);

            // Simulate human thinking time with random delay (1-8 seconds)
            $delay = rand(1, 8);
            
            Log::debug('Agent simulating thinking time', [
                'game_id' => $this->game->id,
                'delay_seconds' => $delay,
            ]);

            sleep($delay);

            // Apply the action to the game
            $this->applyAction($action);

            $totalTime = microtime(true) - $startTime;

            Log::info('CalculateAgentAction job completed', [
                'game_id' => $this->game->id,
                'total_time_seconds' => round($totalTime, 2),
                'calculation_time_ms' => round($calculationTime * 1000, 2),
                'delay_seconds' => $delay,
            ]);

            // Reset error count on successful action
            if ($this->user->isAgent() && $this->user->agent) {
                /** @var \App\Models\Auth\Agent $agentModel */
                $agentModel = $this->user->agent;
                $agentModel->resetErrorCount();
            }

        } catch (\Exception $e) {
            Log::error('CalculateAgentAction job failed', [
                'user_id' => $this->user->id,
                'game_id' => $this->game->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Increment error count for the agent
            if ($this->user->isAgent() && $this->user->agent) {
                /** @var \App\Models\Auth\Agent $agentModel */
                $agentModel = $this->user->agent;
                $agentModel->incrementErrorCount();
            }

            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * Apply the calculated action to the game.
     *
     * @param object $action The action DTO to apply
     * @return void
     */
    protected function applyAction(object $action): void
    {
        // Get the game-specific action handler
        $gameTitle = $this->game->title_slug->value ?? null;

        $actionHandler = match ($gameTitle) {
            // @phpstan-ignore class.notFound
            'checkers' => app(\App\Games\Checkers\Actions\ActionHandler::class),
            // @phpstan-ignore class.notFound
            'hearts' => app(\App\Games\Hearts\Actions\ActionHandler::class),
            // @phpstan-ignore class.notFound, match.alwaysFalse
            'validatefour' => app(\App\Games\ValidateFour\Actions\ActionHandler::class),
            default => throw new \Exception("Unsupported game type: {$gameTitle}"),
        };

        // Apply the action (ActionHandler classes will be implemented in future iterations)
        // @phpstan-ignore-next-line class.notFound
        $actionHandler->apply($this->game, $this->user, $action);

        Log::info('Agent action applied to game', [
            'game_id' => $this->game->id,
            'user_id' => $this->user->id,
            'action_type' => get_class($action),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateAgentAction job permanently failed', [
            'user_id' => $this->user->id,
            'game_id' => $this->game->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // TODO: Notify game that agent encountered an error
        // Could forfeit the game or assign a different agent
    }
}
