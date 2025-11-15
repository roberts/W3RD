<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\AbstractValidateFourMode;
use App\Games\ValidateFour\Actions\DropDisc;
use App\Games\ValidateFour\Actions\PopOut;
use App\Games\ValidateFour\ValidateFourGameState;

class PopOutMode extends AbstractValidateFourMode
{
    /**
     * Validate and apply the given action.
     *
     * @param DropDisc|PopOut $action
     * @param ValidateFourGameState $gameState
     * @return ValidateFourGameState
     * @throws \InvalidArgumentException
     */
    public function applyAction($action, ValidateFourGameState $gameState): ValidateFourGameState
    {
        // Validate the action first
        $this->validateAction($action, $gameState);

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
     * @throws \InvalidArgumentException
     */
    protected function validatePopOutAction(PopOut $action, ValidateFourGameState $gameState): void
    {
        $column = $action->column;
        $board = $gameState->board;
        $currentPlayerUlid = $gameState->currentPlayerUlid;

        // Check if bottom disc exists
        if ($board[count($board) - 1][$column] === null) {
            throw new \InvalidArgumentException("Cannot pop out from empty column $column");
        }

        // Check if bottom disc belongs to current player
        if ($board[count($board) - 1][$column] !== $currentPlayerUlid) {
            throw new \InvalidArgumentException("Cannot pop out opponent's disc from column $column");
        }
    }

    /**
     * Apply a pop_out action to the game state.
     *
     * @param PopOut $action
     * @param ValidateFourGameState $gameState
     * @return ValidateFourGameState
     */
    protected function applyPopOut(PopOut $action, ValidateFourGameState $gameState): ValidateFourGameState
    {
        $board = $gameState->board;
        $column = $action->column;
        $rows = count($board);

        // Remove the bottom disc and shift all discs above it down
        for ($row = $rows - 1; $row > 0; $row--) {
            $board[$row][$column] = $board[$row - 1][$column];
        }
        $board[0][$column] = null; // Top row becomes empty

        // Create new game state with updated board and switched turn
        return new ValidateFourGameState(
            board: $board,
            playerOneUlid: $gameState->playerOneUlid,
            playerTwoUlid: $gameState->playerTwoUlid,
            currentPlayerUlid: $gameState->currentPlayerUlid === $gameState->playerOneUlid
                ? $gameState->playerTwoUlid
                : $gameState->playerOneUlid,
            columns: $gameState->columns,
            rows: $gameState->rows,
            connectCount: $gameState->connectCount,
        );
    }

    /**
     * Override parent validateAction to handle both action types.
     *
     * @param DropDisc|PopOut $action
     * @param ValidateFourGameState $gameState
     * @throws \InvalidArgumentException
     */
    public function validateAction($action, ValidateFourGameState $gameState): void
    {
        if ($action instanceof PopOut) {
            $this->validatePopOutAction($action, $gameState);
        } else {
            parent::validateAction($action, $gameState);
        }
    }
}
