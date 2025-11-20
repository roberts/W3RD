<?php

namespace App\Games;

use App\Enums\GameAttributes\GamePacing;
use App\Enums\GameAttributes\GameSequence;
use App\Enums\GameAttributes\GameVisibility;
use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameArbiterContract;
use App\GameEngine\Interfaces\GameReporterContract;
use App\Models\Game\Action;
use App\Models\Game\Game;

abstract class BaseBoardGameTitle extends BaseGameTitle
{
    // Game Attribute Implementations
    public static function getPacing(): GamePacing
    {
        return GamePacing::TURN_BASED_SYNC;
    }

    public static function getSequence(): GameSequence
    {
        return GameSequence::SEQUENTIAL;
    }

    public static function getVisibility(): GameVisibility
    {
        return GameVisibility::PERFECT_INFORMATION;
    }

    protected const DEFAULT_TURN_TIME_SECONDS = 60;

    abstract protected function getReporter(): GameReporterContract;

    abstract public function getArbiter(): GameArbiterContract;

    public function getTimelimit(): int
    {
        return static::DEFAULT_TURN_TIME_SECONDS;
    }

    /**
     * Check if a given coordinate is within the board boundaries.
     */
    protected function isWithinBounds(int $row, int $col): bool
    {
        /** @var \App\Games\BaseGameState&object{board: array<int, array<int, string|null>>} $gameState */
        $gameState = $this->gameState;
        $board = $gameState->board;
        $rowCount = count($board);
        if ($rowCount === 0) {
            return false;
        }
        $colCount = count($board[0]);

        return $row >= 0 && $row < $rowCount && $col >= 0 && $col < $colCount;
    }

    // GameReporterContract delegation

    public function getPublicStatus(object $gameState): array
    {
        return $this->getReporter()->getPublicStatus($gameState);
    }

    public function describeStateChanges(Game $game, Action $action, object $gameState): array
    {
        return $this->getReporter()->describeStateChanges($game, $action, $gameState);
    }

    public function formatActionSummary(Action $action): string
    {
        return $this->getReporter()->formatActionSummary($action);
    }

    public function getFinishDetails(Game $game, GameOutcome $outcome, object $gameState): array
    {
        return $this->getReporter()->getFinishDetails($game, $outcome, $gameState);
    }

    public function analyzeOutcome(Game $game, GameOutcome $outcome, object $gameState): array
    {
        return $this->getReporter()->analyzeOutcome($game, $outcome, $gameState);
    }

    // GameArbiterContract delegation

    /**
     * Returns the structured rules for this game title.
     */
    public static function getRules(): array
    {
        $rules = parent::getRules();
        $rules['description'] = 'Base description for a board game.';

        return $rules;
    }
}
