<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\AbstractValidateFourMode;
use App\Games\ValidateFour\Actions\DropDisc;
use App\Games\ValidateFour\Actions\PopOut;
use App\Games\ValidateFour\ValidateFourGameState;

class PopOutMode extends AbstractValidateFourMode
{
    /**
     * Override parent applyAction to handle both drop_disc and pop_out actions.
     *
     * @param DropDisc|PopOut $action
     * @param ValidateFourGameState $gameState
     * @return ValidateFourGameState
     */
    public function applyAction($action, $gameState): object
    {
        if (!($gameState instanceof ValidateFourGameState)) {
            return $gameState;
        }

        // Handle pop_out action
        if ($action instanceof PopOut) {
            return $this->applyPopOut($action, $gameState);
        }

        // For drop_disc, use parent implementation
        return parent::applyAction($action, $gameState);
    }

    /**
     * Validate a pop_out action.
     *
     * @param PopOut $action
     * @param ValidateFourGameState $gameState
     * @return bool
     */
    protected function validatePopOutAction(PopOut $action, ValidateFourGameState $gameState): bool
    {
        $column = $action->column;
        
        // Check column is valid
        if ($column < 0 || $column >= $gameState->columns) {
            return false;
        }

        // Check if bottom disc exists (bottom row is rows - 1)
        $bottomRow = $gameState->rows - 1;
        $bottomDisc = $gameState->getDiscAt($bottomRow, $column);
        if ($bottomDisc === null) {
            return false;
        }

        // Check if bottom disc belongs to current player
        if ($bottomDisc !== $gameState->currentPlayerUlid) {
            return false;
        }

        return true;
    }

    /**
     * Apply a pop_out action to the game state.
     * Returns a new immutable game state with the disc popped and column shifted.
     *
     * @param PopOut $action
     * @param ValidateFourGameState $gameState
     * @return ValidateFourGameState
     */
    protected function applyPopOut(PopOut $action, ValidateFourGameState $gameState): ValidateFourGameState
    {
        $newBoard = $gameState->board;
        $column = $action->column;

        // Remove the bottom disc and shift all discs above it down
        for ($row = $gameState->rows - 1; $row > 0; $row--) {
            $newBoard[$row][$column] = $newBoard[$row - 1][$column];
        }
        $newBoard[0][$column] = null; // Top row becomes empty

        // Return new state with updated board and switched turn
        return $gameState
            ->withBoard($newBoard)
            ->withNextPlayer();
    }

    /**
     * Override parent validateAction to handle both action types.
     *
     * @param DropDisc|PopOut $action
     * @param ValidateFourGameState $gameState
     * @return bool
     */
    public function validateAction($action, $gameState): bool
    {
        if (!($gameState instanceof ValidateFourGameState)) {
            return false;
        }

        if ($action instanceof PopOut) {
            return $this->validatePopOutAction($action, $gameState);
        }

        return parent::validateAction($action, $gameState);
    }
}
