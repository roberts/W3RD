<?php

declare(strict_types=1);

namespace App\GameTitles\Checkers;

use App\Enums\GameAttributes\GameComplexity;
use App\Enums\GameAttributes\GameDynamic;
use App\Exceptions\InvalidGameConfigurationException;
use App\GameEngine\Interfaces\GameTitleContract;
use App\GameTitles\BaseBoardGameTitle;
use App\GameTitles\Checkers\Actions\CheckersActionMapper;
use App\Models\Game\Game;

/**
 * Base Checkers game implementation.
 *
 * Implements standard American Checkers (English Draughts) rules.
 */
abstract class CheckersProtocol extends BaseBoardGameTitle implements GameTitleContract
{
    // Game Attribute Implementations
    public static function getDynamic(): GameDynamic
    {
        return GameDynamic::ONE_VS_ONE;
    }

    public static function getAdditionalAttributes(): array
    {
        return [
            GameComplexity::class => GameComplexity::MIDCORE,
        ];
    }

    abstract protected function getGameConfig(): CheckersConfig;

    abstract public function getArbiter(): CheckersArbiter;

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

    public function getAvailableActions(object $gameState, string $playerUlid): array
    {
        // Will be implemented with game mode
        return [];
    }
}
