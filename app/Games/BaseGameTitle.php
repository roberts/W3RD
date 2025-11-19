<?php

namespace App\Games;

use App\Games\Interfaces\GameReportingInterface;
use App\Interfaces\GameTitleContract;
use App\Models\Game\Action;
use App\Models\Game\Game;

abstract class BaseGameTitle implements GameTitleContract, GameReportingInterface
{
    protected Game $game;

    protected BaseGameState $gameState;

    public function __construct(Game $game)
    {
        $this->game = $game;

        $gameStateClass = $this->getGameStateClass();
        $this->gameState = $gameStateClass::fromArray($game->game_state);
    }

    /**
     * Returns the fully qualified class name of the game state object.
     */
    abstract protected function getGameStateClass(): string;

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getGameState(): BaseGameState
    {
        return $this->gameState;
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

    // Default implementations for GameReportingInterface

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
