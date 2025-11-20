<?php

namespace App\Games\ValidateFour;

use App\Enums\GameErrorCode;
use App\Enums\GameAttributes\GameComplexity;
use App\Enums\GameAttributes\GameContinuity;
use App\Enums\GameAttributes\GameDynamic;
use App\Enums\GameAttributes\GameEntryPolicy;
use App\Enums\GameAttributes\GameLifecycle;
use App\Enums\GameAttributes\GamePacing;
use App\Enums\GameAttributes\GameSequence;
use App\Enums\GameAttributes\GameVisibility;
use App\Exceptions\InvalidGameConfigurationException;
use App\GameEngine\ValidationResult;
use App\Games\BaseBoardGameTitle;
use App\Games\ValidateFour\Actions\ValidateFourActionMapper;

abstract class ValidateFourProtocol extends BaseBoardGameTitle
{
    // Game Attribute Implementations
    public static function getDynamic(): GameDynamic
    {
        return GameDynamic::ONE_VS_ONE;
    }

    public static function getAdditionalAttributes(): array
    {
        return [
            GameComplexity::class => GameComplexity::CASUAL,
        ];
    }

    protected const DEFAULT_TURN_TIME_SECONDS = 30;

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

    public function getAvailableActions(object $gameState, string $playerUlid): array
    {
        if (! ($gameState instanceof ValidateFourBoard)) {
            return [];
        }

        return $this->kernel->getAvailableActions($gameState, $playerUlid);
    }
}
