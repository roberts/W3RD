<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\AbstractValidateFourMode;
use App\Games\ValidateFour\ValidateFourGameState;

class EightBySevenMode extends AbstractValidateFourMode
{
    /**
     * Create a new game state for 8x7 mode.
     *
     * @param string $playerOneUlid
     * @param string $playerTwoUlid
     * @return ValidateFourGameState
     */
    public function createInitialState(string $playerOneUlid, string $playerTwoUlid): ValidateFourGameState
    {
        return new ValidateFourGameState(
            board: [],
            playerOneUlid: $playerOneUlid,
            playerTwoUlid: $playerTwoUlid,
            currentPlayerUlid: $playerOneUlid,
            columns: 8,
            rows: 7,
            connectCount: 4,
        );
    }
}
