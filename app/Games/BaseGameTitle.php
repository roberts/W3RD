<?php

namespace App\Games;

use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameConfigContract;
use App\GameEngine\Interfaces\GameReporterContract;
use App\GameEngine\Interfaces\GameTitleContract;
use App\GameEngine\Kernel\GameKernel;
use App\GameEngine\ValidationResult;
use App\Models\Game\Action;
use App\Models\Game\Game;

abstract class BaseGameTitle implements GameReporterContract, GameTitleContract
{
    protected Game $game;

    protected object $gameState;

    protected GameKernel $kernel;

    public function __construct(Game $game)
    {
        $this->game = $game;

        $gameStateClass = $this->getGameStateClass();
        $this->gameState = $gameStateClass::fromArray($game->game_state);

        $this->kernel = new GameKernel($this->getGameConfig());
    }

    /**
     * Returns the fully qualified class name of the game state object.
     */
    abstract protected function getGameStateClass(): string;

    /**
     * Returns the game configuration object.
     */
    abstract protected function getGameConfig(): GameConfigContract;

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getGameState(): BaseGameState
    {
        return $this->gameState;
    }

    public function validateAction(object $gameState, object $action): ValidationResult
    {
        return $this->kernel->validateAction($gameState, $action);
    }

    public function applyAction(object $gameState, object $action): object
    {
        return $this->kernel->applyAction($gameState, $action);
    }

    /**
     * Returns the structured rules for this game title.
     */
    public static function getRules(): array
    {
        return [
            'title' => 'Game Title',
            'description' => 'Base description for a game.',
            'sections' => [],
        ];
    }

    // Default implementations for GameReporterContract

    public function getPublicStatus(object $gameState): array
    {
        return [];
    }

    public function describeStateChanges(Game $game, Action $action, object $gameState): array
    {
        $changes = [];

        // Check for phase transitions
        if (isset($gameState->phase) && isset($game->game_state['phase']) && $gameState->phase->value !== $game->game_state['phase']) {
            $changes['phase_transition'] = $gameState->phase->value;
        }

        return $changes;
    }

    public function formatActionSummary(Action $action): string
    {
        return sprintf(
            '%s performed %s',
            $action->player->user->username,
            $action->action_type->value
        );
    }

    public function getFinishDetails(Game $game, GameOutcome $outcome, object $gameState): array
    {
        $reason = $outcome->details['reason'] ?? null;

        return [
            'reason_text' => $reason ? ucwords(str_replace('_', ' ', $reason)) : null,
        ];
    }

    public function analyzeOutcome(Game $game, GameOutcome $outcome, object $gameState): array
    {
        $startTime = $game->started_at ?? $game->created_at;
        $endTime = $game->completed_at ?? now();

        return [
            'duration_seconds' => $startTime->diffInSeconds($endTime),
            'total_turns' => $game->turn_number ?? 0,
        ];
    }
}
