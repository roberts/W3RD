<?php

namespace App\Games\ValidateFour;

use App\Enums\GameErrorCode;
use App\Exceptions\InvalidGameConfigurationException;
use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameReporterContract;
use App\GameEngine\ValidationResult;
use App\Games\BaseBoardGameTitle;
use App\Games\ValidateFour\Actions\ValidateFourActionMapper;
use App\Models\Game\Action;
use App\Models\Game\Game;
use Carbon\Carbon;

abstract class ValidateFourProtocol extends BaseBoardGameTitle
{
    protected const DEFAULT_TURN_TIME_SECONDS = 30;

    protected const NETWORK_GRACE_PERIOD_SECONDS = 2;

    protected const DEFAULT_TIMEOUT_PENALTY = 'forfeit';

    abstract protected function getGameConfig(): ValidateFourConfig;

    abstract protected function getArbiter(): ValidateFourArbiter;

    abstract protected function getReporter(): ValidateFourReporter;

    protected function getGameStateClass(): string
    {
        return ValidateFourBoard::class;
    }

    public function createInitialState(string ...$playerUlids): object
    {
        if (count($playerUlids) !== 2) {
            throw new InvalidGameConfigurationException(
                'Validate Four requires exactly 2 players',
                'validate-four',
                ['player_count' => count($playerUlids)]
            );
        }

        $config = $this->kernel->getConfig()->getInitialStateConfig();

        return ValidateFourBoard::createNew(
            playerOneUlid: $playerUlids[0],
            playerTwoUlid: $playerUlids[1],
            columns: $config['columns'],
            rows: $config['rows'],
            connectCount: $config['connectCount']
        );
    }

    public function getStateClass(): string
    {
        return ValidateFourBoard::class;
    }

    public function getActionMapper(): string
    {
        return ValidateFourActionMapper::class;
    }

    public static function getRules(): array
    {
        return [
            'title' => 'Validate Four',
            'description' => 'Be the first player to connect four of your pieces in a row—horizontally, vertically, or diagonally.',
            'sections' => [
                [
                    'title' => 'Core Gameplay',
                    'content' => <<<'MARKDOWN'
                    *   Players take turns dropping one of their colored pieces from the top into a column.
                    *   The piece falls to the lowest available space within the column.
                    *   The first player to form a line of four of their pieces wins.
                    MARKDOWN,
                ],
            ],
        ];
    }

    public function validateAction(object $gameState, object $action): ValidationResult
    {
        if (! ($gameState instanceof ValidateFourBoard)) {
            return ValidationResult::invalid(
                GameErrorCode::INVALID_STATE->value,
                GameErrorCode::INVALID_STATE->message()
            );
        }

        return $this->kernel->validateAction($gameState, $action);
    }

    public function applyAction(object $gameState, object $action): object
    {
        return $this->kernel->applyAction($gameState, $action);
    }

    public function checkEndCondition(object $gameState): GameOutcome
    {
        return $this->getArbiter()->checkWinCondition($gameState);
    }

    public function getAvailableActions(object $gameState, string $playerUlid): array
    {
        if (! ($gameState instanceof ValidateFourBoard)) {
            return [];
        }

        return $this->kernel->getAvailableActions($gameState, $playerUlid);
    }

    public function getTimelimit(): int
    {
        return static::DEFAULT_TURN_TIME_SECONDS;
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

    // GameReporterContract implementation

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
}
