<?php

namespace App\Games\ConnectFour;

use App\Enums\GameAttributes\GameComplexity;
use App\Enums\GameAttributes\GameDynamic;
use App\Enums\GameErrorCode;
use App\Exceptions\InvalidGameConfigurationException;
use App\GameEngine\ValidationResult;
use App\Games\BaseBoardGameTitle;
use App\Games\ConnectFour\Actions\ConnectFourActionMapper;

abstract class ConnectFourProtocol extends BaseBoardGameTitle
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

    abstract protected function getGameConfig(): ConnectFourConfig;

    abstract public function getArbiter(): ConnectFourArbiter;

    abstract protected function getReporter(): ConnectFourReporter;

    protected function getGameStateClass(): string
    {
        return ConnectFourBoard::class;
    }

    public function createInitialState(string ...$playerUlids): object
    {
        if (count($playerUlids) !== 2) {
            throw new InvalidGameConfigurationException(
                'Connect Four requires exactly 2 players',
                'connect-four',
                ['player_count' => count($playerUlids)]
            );
        }

        $config = $this->kernel->getConfig()->getInitialStateConfig();

        return ConnectFourBoard::createNew(
            playerOneUlid: $playerUlids[0],
            playerTwoUlid: $playerUlids[1],
            columns: $config['columns'],
            rows: $config['rows'],
            connectCount: $config['connectCount']
        );
    }

    public function getStateClass(): string
    {
        return ConnectFourBoard::class;
    }

    public function getActionMapper(): string
    {
        return ConnectFourActionMapper::class;
    }

    public static function getRules(): array
    {
        return [
            'title' => 'Connect Four',
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
        if (! ($gameState instanceof ConnectFourBoard)) {
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
        if (! ($gameState instanceof ConnectFourBoard)) {
            return [];
        }

        return $this->kernel->getAvailableActions($gameState, $playerUlid);
    }
}
