<?php

declare(strict_types=1);

namespace App\Games\Checkers;

use App\Exceptions\InvalidGameConfigurationException;
use App\Games\BaseBoardGameTitle;
use App\GameEngine\GameOutcome;
use App\GameEngine\ValidationResult;
use App\GameEngine\Interfaces\GameTitleContract;
use App\Models\Game\Action;
use App\Models\Game\Game;
use Carbon\Carbon;
use App\GameEngine\Kernel\GameKernel;
use App\GameEngine\Interfaces\GameConfigContract;
use App\Games\Checkers\Actions\CheckersActionMapper;

/**
 * Base Checkers game implementation.
 *
 * Implements standard American Checkers (English Draughts) rules.
 */
abstract class CheckersProtocol extends BaseBoardGameTitle implements GameTitleContract
{
    protected const DEFAULT_TURN_TIME_SECONDS = 60;

    protected const NETWORK_GRACE_PERIOD_SECONDS = 2;

    protected const DEFAULT_TIMEOUT_PENALTY = 'forfeit';

    abstract protected function getGameConfig(): CheckersConfig;

    abstract protected function getArbiter(): CheckersArbiter;

    abstract protected function getReporter(): CheckersReporter;

    public function createInitialState(string ...$playerUlids): object
    {
        if (count($playerUlids) !== 2) {
            throw new InvalidGameConfigurationException(
                'Checkers requires exactly 2 players',
                'checkers',
                ['player_count' => count($playerUlids)]
            );
        }

        return CheckersBoard::createNew(
            playerOneUlid: $playerUlids[0],
            playerTwoUlid: $playerUlids[1],
        );
    }

    public function getStateClass(): string
    {
        return CheckersBoard::class;
    }

    protected function getGameStateClass(): string
    {
        return CheckersBoard::class;
    }

    public function getActionMapper(): string
    {
        return CheckersActionMapper::class;
    }

    public function checkEndCondition(object $gameState): GameOutcome
    {
        return $this->getArbiter()->checkWinCondition($gameState) ?? GameOutcome::inProgress();
    }

    public function getAvailableActions(object $gameState, string $playerUlid): array
    {
        // Will be implemented with game mode
        return [];
    }

    public function getTimelimit(): int
    {
        return self::DEFAULT_TURN_TIME_SECONDS;
    }

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

    public function getActionDeadline(object $gameState, Game $game): Carbon
    {
        return $game->getRecentActionTime()->addSeconds(
            $this->getTimelimit() + self::NETWORK_GRACE_PERIOD_SECONDS
        );
    }

    public function getTimeoutPenalty(): string
    {
        return self::DEFAULT_TIMEOUT_PENALTY;
    }

}
