<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\BaseValidateFourMode;
use App\Games\ValidateFour\ValidateFourGameState;

class NineBySixMode extends BaseValidateFourMode
{
    /**
     * Create initial game state for 9x6 mode.
     *
     * @param string ...$playerUlids Player ULIDs (must be exactly 2)
     * @return ValidateFourGameState
     * @throws \InvalidArgumentException If not exactly 2 players provided
     */
    public function createInitialState(string ...$playerUlids): object
    {
        if (count($playerUlids) !== 2) {
            throw new \InvalidArgumentException('Validate Four requires exactly 2 players');
        }

        return ValidateFourGameState::createNew(
            playerOneUlid: $playerUlids[0],
            playerTwoUlid: $playerUlids[1],
            columns: 9,
            rows: 6,
            connectCount: 4
        );
    }
}
