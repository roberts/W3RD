<?php

namespace App\Games;

use App\Enums\GameAttributes\GameContinuity;
use App\Enums\GameAttributes\GameDynamic;
use App\Enums\GameAttributes\GameEntryPolicy;
use App\Enums\GameAttributes\GameLifecycle;
use App\Enums\GameAttributes\GamePacing;
use App\Enums\GameAttributes\GameSequence;
use App\Enums\GameAttributes\GameTimer;
use App\Enums\GameAttributes\GameVisibility;
use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameConfigContract;
use App\GameEngine\Interfaces\GameReporterContract;
use App\GameEngine\Interfaces\GameTitleContract;
use App\GameEngine\Kernel\GameKernel;
use App\GameEngine\ValidationResult;
use App\Models\Auth\User;
use App\Models\Game\Action;
use App\Models\Game\Game;
use Carbon\Carbon;

abstract class BaseGameTitle implements GameReporterContract, GameTitleContract
{
    // Game Attribute Implementations
    public static function getContinuity(): GameContinuity
    {
        return GameContinuity::MATCH_BASED;
    }

    public static function getEntryPolicy(): GameEntryPolicy
    {
        return GameEntryPolicy::LOCKED_ON_START;
    }

    public static function getLifecycle(): GameLifecycle
    {
        return GameLifecycle::STANDALONE;
    }

    public static function getAdditionalAttributes(): array
    {
        return [];
    }

    protected const NETWORK_GRACE_PERIOD_SECONDS = 2;

    protected const DEFAULT_TIMEOUT_PENALTY = 'forfeit';

    protected Game $game;

    protected object $gameState;

    protected GameKernel $kernel;

    public function __construct(
        Game $game,
    ) {
        $this->game = $game;

        $gameStateClass = $this->getGameStateClass();
        $this->gameState = $gameStateClass::fromArray($game->game_state);

        $this->kernel = new GameKernel(
            config: $this->getGameConfig(),
            gameTitle: $this,
        );
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

    public function validatePlayerAction(User $player, object $action): ValidationResult
    {
        return $this->kernel->validatePlayerAction($this->game, $this->gameState, $player, $action);
    }

    public function validateAction(object $gameState, object $action): ValidationResult
    {
        return $this->kernel->validateAction($gameState, $action);
    }

    public function applyAction(object $gameState, object $action): object
    {
        $this->gameState = $this->kernel->applyAction($gameState, $action);

        return $this->gameState;
    }

    public function advanceGame(): void
    {
        $this->game = $this->kernel->advanceGame($this->game);
    }

    public function getRedactedStateForPlayer(User $player): object
    {
        return $this->kernel->redactStateForPlayer($this->gameState, $player);
    }

    public function getActionDeadline(object $gameState, Game $game): Carbon
    {
        return $game->getRecentActionTime()->addSeconds(
            $this->getTimelimit() + static::NETWORK_GRACE_PERIOD_SECONDS
        );
    }

    public function getTimeoutPenalty(): string
    {
        return static::DEFAULT_TIMEOUT_PENALTY;
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

    // These need to be implemented by the concrete game/mode classes
    abstract public static function getPacing(): GamePacing;

    abstract public static function getTimer(): GameTimer;

    abstract public static function getSequence(): GameSequence;

    abstract public static function getVisibility(): GameVisibility;

    abstract public static function getDynamic(): GameDynamic;
}
