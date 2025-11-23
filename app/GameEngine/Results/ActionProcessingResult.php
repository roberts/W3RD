<?php

declare(strict_types=1);

namespace App\GameEngine\Results;

use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameTitleContract;
use App\GameEngine\ValidationResult;
use App\Models\Games\Action;
use App\Models\Games\Game;
use Carbon\Carbon;

/**
 * Rich result object returned by GameEngine after processing a player action.
 *
 * Contains all data needed by the controller to build an HTTP response,
 * including game state, recorded action, outcome details, and metadata.
 */
readonly class ActionProcessingResult
{
    private function __construct(
        public bool $success,
        public Game $game,
        public object $gameState,
        public ?Action $actionRecord,
        public ?GameOutcome $outcome,
        public array $stateChanges,
        public array $availableActions,
        public ?Carbon $nextActionDeadline,
        public ?string $actionSummary,
        public ?ValidationResult $validationError,
        public array $context,
        public ?GameTitleContract $mode = null,
    ) {}

    /**
     * Create a successful result after action processing.
     */
    public static function success(
        Game $game,
        object $gameState,
        Action $actionRecord,
        GameTitleContract $mode,
        array $context = [],
    ): self {
        return new self(
            success: true,
            game: $game,
            gameState: $gameState,
            actionRecord: $actionRecord,
            outcome: null,
            stateChanges: $context['state_changes'] ?? [],
            availableActions: $context['available_actions'] ?? [],
            nextActionDeadline: $mode->getActionDeadline($gameState, $game),
            actionSummary: $context['action_summary'] ?? null,
            validationError: null,
            context: $context,
            mode: $mode,
        );
    }

    /**
     * Create a failed result due to validation error.
     */
    public static function validationFailed(
        Game $game,
        object $gameState,
        ValidationResult $validationError,
    ): self {
        return new self(
            success: false,
            game: $game,
            gameState: $gameState,
            actionRecord: null,
            outcome: null,
            stateChanges: [],
            availableActions: [],
            nextActionDeadline: null,
            actionSummary: null,
            validationError: $validationError,
            context: [],
        );
    }

    /**
     * Create a failed result due to timer expiration.
     */
    public static function timerExpired(object $timerResult): self
    {
        return new self(
            success: false,
            game: $timerResult->game,
            gameState: $timerResult->gameState,
            actionRecord: null,
            outcome: null,
            stateChanges: [],
            availableActions: [],
            nextActionDeadline: null,
            actionSummary: null,
            validationError: null,
            context: ['timer_expired' => true, 'timer_result' => $timerResult],
        );
    }

    /**
     * Convert to array format suitable for JSON response.
     */
    public function toResponseArray(): array
    {
        if (! $this->success) {
            return [
                'success' => false,
                'error' => $this->validationError->message ?? 'Action failed',
                'error_code' => $this->validationError->errorCode ?? 'unknown',
            ];
        }

        return [
            'success' => true,
            'action' => [
                'ulid' => $this->actionRecord?->ulid,
                'summary' => $this->actionSummary,
            ],
            'game' => [
                'ulid' => $this->game->ulid,
                'status' => $this->game->status->value,
                'game_state' => $this->mode?->getRedactor()->redact($this->game, auth()->user()) ?? $this->game->game_state,
                'winner_ulid' => $this->gameState->winnerUlid ?? null,
                'is_draw' => $this->gameState->isDraw ?? false,
                'outcome_type' => $this->game->outcome_type?->value,
                'outcome_details' => $this->game->outcome_details,
            ],
            'context' => $this->context,
            'outcome' => $this->game->outcome_details,
            'next_action_deadline' => $this->nextActionDeadline?->toIso8601String(),
            'timeout' => $this->context['timeout'] ?? null,
        ];
    }
}
