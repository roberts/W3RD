<?php

declare(strict_types=1);

namespace App\GameEngine;

use App\GameEngine\Interfaces\GameTitleContract;
use App\GameEngine\Lifecycle\Conclusion\ConclusionManager;
use App\GameEngine\Lifecycle\Progression\AgentCoordinator;
use App\GameEngine\Lifecycle\Progression\CoordinatedActionProcessor;
use App\GameEngine\Lifecycle\Progression\TurnManager;
use App\GameEngine\Results\ActionProcessingResult;
use App\GameEngine\Timeline\ActionRecorder;
use App\GameEngine\Timers\TimerExpiredHandler;
use App\GameEngine\Timers\TimerScheduler;
use App\GameTitles\BaseGameTitle;
use App\Models\Games\Game;
use App\Models\Games\Player;
use App\Services\Games\GameResponseEnhancementService;

/**
 * GameEngine - Main orchestrator for game action processing.
 *
 * This class manages the complete lifecycle of a player action:
 * 1. Pre-flight validation (timer checks, turn verification)
 * 2. Pure action processing via GameKernel
 * 3. Side effects (persistence, coordination, recording)
 * 4. Game progression (turns, conclusion, timers)
 * 5. Agent triggering
 *
 * Responsibilities:
 * - Orchestrates the workflow (coordinates multiple services)
 * - Manages side effects (DB writes, events, jobs)
 * - Owns game lifecycle state machine
 *
 * Does NOT:
 * - Validate actions (delegates to GameKernel)
 * - Apply game rules (delegates to GameMode)
 * - Handle HTTP concerns (controller responsibility)
 */
class GameEngine
{
    public function __construct(
        private CoordinatedActionProcessor $coordinatedProcessor,
        private TurnManager $turnManager,
        private ConclusionManager $conclusionManager,
        private TimerExpiredHandler $timerHandler,
        private TimerScheduler $timerScheduler,
        private AgentCoordinator $agentCoordinator,
        private ActionRecorder $actionRecorder,
        private GameResponseEnhancementService $enhancementService,
    ) {}

    /**
     * Process a player action through the complete game lifecycle.
     *
     * This is the main entry point for all game action processing.
     * It orchestrates validation, application, persistence, and progression.
     *
     * @param  Game  $game  The game instance
     * @param  Player  $player  The player taking the action
     * @param  GameTitleContract  $mode  The game mode handler
     * @param  object  $action  The action DTO
     * @return ActionProcessingResult Rich result with state and metadata
     */
    public function processPlayerAction(
        Game $game,
        Player $player,
        GameTitleContract $mode,
        object $action
    ): ActionProcessingResult {
        // ── 1. PRE-FLIGHT CHECKS ────────────────────────────────────
        // Restore game state from JSON
        $stateClass = $mode->getStateClass();
        $gameState = $stateClass::fromArray($game->game_state ?? []);

        // Check if current turn has timed out
        $timerResult = $this->timerHandler->checkAndHandle($game, $mode, $gameState);
        if ($timerResult->hasExpired) {
            return ActionProcessingResult::timerExpired($timerResult);
        }

        // Verify it's this player's turn
        if ($gameState->currentPlayerUlid !== $player->ulid) {
            $validationResult = ValidationResult::invalid(
                'not_player_turn',
                "It is not {$player->user->username}'s turn"
            );

            $this->actionRecorder->recordFailure(
                $game,
                $player,
                $action,
                $validationResult,
                $game->turn_number ?? 1
            );

            return ActionProcessingResult::validationFailed(
                $game,
                $gameState,
                $validationResult
            );
        }

        // ── 2. DELEGATE TO MODE (validates and applies via its kernel) ──
        // The mode's kernel validates and applies the action without side effects
        $validationResult = $mode->validateAction($gameState, $action);

        if (! $validationResult->isValid) {
            // Record failed action for debugging
            $this->actionRecorder->recordFailure(
                $game,
                $player,
                $action,
                $validationResult,
                $game->turn_number ?? 1
            );

            return ActionProcessingResult::validationFailed(
                $game,
                $gameState,
                $validationResult
            );
        }

        // Apply the action through the mode
        $gameState = $mode->applyAction($gameState, $action);

        // ── 3. SIDE EFFECTS & PERSISTENCE ───────────────────────────
        // Save the updated game state
        $game->game_state = $gameState->toArray();
        $game->save();

        // Handle coordinated actions (e.g., Hearts card passing)
        $coordinationResult = $this->coordinatedProcessor->process(
            $game,
            $action,
            $mode,
            $gameState
        );

        // Record the successful action
        $actionRecord = $this->actionRecorder->recordSuccess(
            $game,
            $player,
            $action,
            $game->turn_number ?? 1,
            $coordinationResult->coordinationGroup,
            $coordinationResult->coordinationSequence
        );

        // ── 4. COORDINATION COMPLETION ──────────────────────────────
        // If coordination is complete, update with final state
        if ($coordinationResult->coordinationComplete && $coordinationResult->updatedGameState) {
            $gameState = $coordinationResult->updatedGameState;
            $game->game_state = $gameState->toArray();
            $game->save();
        }

        // ── 5. GAME LIFECYCLE PROGRESSION ───────────────────────────
        // Check for end condition
        $this->conclusionManager->determineOutcome($game);
        $game->refresh(); // Refresh to get status changes

        // Only progress if game is still active
        if ($game->status->isActive()) {
            $this->turnManager->advanceTurn($game, $mode);
            $this->timerScheduler->scheduleForNextPlayer($game, $mode);
            $game->refresh();
            // Refresh state after turn advancement
            $gameState = $stateClass::fromArray($game->game_state ?? []);
        }

        // ── 6. AGENT COORDINATION ───────────────────────────────────
        // Trigger agent action if next player is an agent
        $this->agentCoordinator->triggerIfAgentTurn($game, $gameState, $mode);

        // ── 7. GENERATE RICH CONTEXT ────────────────────────────────
        // Build detailed context for response
        // Cast to BaseGameTitle for service compatibility
        /** @var BaseGameTitle $baseMode */
        $baseMode = $mode;
        $context = $this->enhancementService->generateActionContext(
            $game,
            $gameState,
            $baseMode,
            $actionRecord
        );

        // Add timeout information to context
        $context['timeout'] = [
            'timelimit_seconds' => $mode->getTimelimit(),
            'grace_period_seconds' => 2,
            'penalty' => $mode->getTimeoutPenalty(),
        ];

        // ── 8. RETURN RESULT ────────────────────────────────────────
        return ActionProcessingResult::success(
            game: $game,
            gameState: $gameState,
            actionRecord: $actionRecord,
            mode: $mode,
            context: $context,
        );
    }
}
