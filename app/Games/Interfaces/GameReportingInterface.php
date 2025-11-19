<?php

declare(strict_types=1);

namespace App\Games\Interfaces;

use App\Games\GameOutcome;
use App\Models\Game\Action;
use App\Models\Game\Game;

/**
 * Contract for game reporting and analysis.
 *
 * This interface defines how a game projects its internal state into
 * human-readable summaries, API responses, and analytical data.
 */
interface GameReportingInterface
{
    /**
     * Get game-specific context information for the API response.
     *
     * Examples:
     * - Checkers: Piece counts, king counts
     * - Hearts: Current scores, hearts broken status
     * - Connect 4: Pieces played, columns available
     *
     * @param  object  $gameState  The current game state
     * @return array<string, mixed>
     */
    public function getPublicStatus(object $gameState): array;

    /**
     * Detect and describe state changes resulting from an action.
     *
     * Examples:
     * - "Piece promoted to King"
     * - "Hearts have been broken"
     * - "Trick completed"
     *
     * @param  Game  $game  The game model
     * @param  Action  $action  The action that caused the change
     * @param  object  $gameState  The current (post-action) game state
     * @return array<string> List of human-readable change descriptions
     */
    public function describeStateChanges(Game $game, Action $action, object $gameState): array;

    /**
     * Format an action into a human-readable summary.
     *
     * Example: "Player1 dropped a piece in column 4"
     *
     * @param  Action  $action  The action to summarize
     * @return string
     */
    public function formatActionSummary(Action $action): string;

    /**
     * Format finish details explaining how the game ended.
     *
     * @param  Game  $game  The game model
     * @param  GameOutcome  $outcome  The outcome of the game
     * @param  object  $gameState  The final game state
     * @return array<string, mixed>
     */
    public function getFinishDetails(Game $game, GameOutcome $outcome, object $gameState): array;

    /**
     * Analyze game outcome for insights and statistics.
     *
     * @param  Game  $game  The game model
     * @param  GameOutcome  $outcome  The outcome of the game
     * @param  object  $gameState  The final game state
     * @return array<string, mixed>
     */
    public function analyzeOutcome(Game $game, GameOutcome $outcome, object $gameState): array;
}
