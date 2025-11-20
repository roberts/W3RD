<?php

declare(strict_types=1);

namespace App\Games\Checkers;

use App\Exceptions\InvalidGameConfigurationException;
use App\GameEngine\GameOutcome;
use App\GameEngine\Interfaces\GameTitleContract;
use App\Games\BaseBoardGameTitle;
use App\Games\Checkers\Actions\CheckersActionMapper;
use App\Models\Game\Action;
use App\Models\Game\Game;
use Carbon\Carbon;

/**
 * Base Checkers game implementation.
 *
 * Implements standard American Checkers (English Draughts) rules.
 */
abstract class CheckersProtocol extends BaseBoardGameTitle implements GameTitleContract
{
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

    public function getAvailableActions(object $gameState, string $playerUlid): array
    {
        // Will be implemented with game mode
        return [];
    }
}
