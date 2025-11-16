<?php

namespace App\Games;

abstract class BaseBoardGameTitle extends BaseGameTitle
{
    /**
     * Check if a given coordinate is within the board boundaries.
     */
    protected function isWithinBounds(int $row, int $col): bool
    {
        $board = $this->gameState->board;
        $rowCount = count($board);
        if ($rowCount === 0) {
            return false;
        }
        $colCount = count($board[0]);

        return $row >= 0 && $row < $rowCount && $col >= 0 && $col < $colCount;
    }

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
