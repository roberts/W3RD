<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\Actions\PopOut;
use App\Games\ValidateFour\BaseValidateFour;
use App\Games\ValidateFour\GameState;
use App\Games\ValidationResult;

class PopOutMode extends BaseValidateFour
{
    /**
     * Override parent validateAction to handle both drop_disc and pop_out actions.
     */
    public function validateAction(object $gameState, object $action): ValidationResult
    {
        if (! ($gameState instanceof GameState)) {
            return ValidationResult::invalid(
                'INVALID_STATE_TYPE',
                'Game state must be a GameState instance'
            );
        }

        if ($action instanceof PopOut) {
            return $this->validatePopOutAction($gameState, $action);
        }

        return parent::validateAction($gameState, $action);
    }

    /**
     * Override parent applyAction to handle both drop_disc and pop_out actions.
     */
    public function applyAction(object $gameState, object $action): object
    {
        if (! ($gameState instanceof GameState)) {
            return $gameState;
        }

        // Handle pop_out action
        if ($action instanceof PopOut) {
            return $this->applyPopOut($gameState, $action);
        }

        // For drop_disc, use parent implementation
        return parent::applyAction($gameState, $action);
    }

    /**
     * Override getAvailableActions to include pop_out option.
     *
     * @return array<string, mixed>
     */
    public function getAvailableActions(object $gameState, string $playerUlid): array
    {
        $actions = parent::getAvailableActions($gameState, $playerUlid);

        if (! ($gameState instanceof GameState)) {
            return $actions;
        }

        // If not player's turn, no actions available
        if ($gameState->currentPlayerUlid !== $playerUlid) {
            return [];
        }

        // Find columns where player can pop out (has disc at bottom)
        $bottomRow = $gameState->rows - 1;
        $popOutColumns = [];

        for ($col = 0; $col < $gameState->columns; $col++) {
            $bottomDisc = $gameState->getDiscAt($bottomRow, $col);
            if ($bottomDisc === $playerUlid) {
                $popOutColumns[] = $col;
            }
        }

        $actions['pop_out'] = [
            'columns' => $popOutColumns,
        ];

        return $actions;
    }

    /**
     * Returns the complete rules for the Pop-Out mode.
     *
     * Merges its specific rules with the base Validate Four rules.
     */
    public static function getRules(): array
    {
        $baseRules = parent::getRules();

        $popOutRules = [
            'name' => 'Pop Out',
            'description' => 'A variant where you can remove a disc from the bottom row instead of dropping one.',
            'sections' => [
                [
                    'title' => 'Special Rule: Popping Out',
                    'content' => <<<'MARKDOWN'
                    On your turn, you may choose to **pop out** one of your own discs from the **bottom row**.

                    *   This removes the disc from the board.
                    *   All discs in the column above it will fall down one space.
                    *   You cannot pop a disc if it is the only one in its column.
                    MARKDOWN,
                ],
            ],
        ];

        // Merge the Pop-Out rules into the base rules
        $baseRules['sections'] = array_merge($baseRules['sections'], $popOutRules['sections']);
        $baseRules['description'] = $popOutRules['description'];
        $baseRules['name'] = $popOutRules['name'];

        return $baseRules;
    }

    /**
     * Validate a pop_out action.
     */
    protected function validatePopOutAction(GameState $gameState, PopOut $action): ValidationResult
    {
        $column = $action->column;

        // Check column is valid
        if ($column < 0 || $column >= $gameState->columns) {
            return ValidationResult::invalid(
                'INVALID_COLUMN',
                sprintf('Column must be between 0 and %d', $gameState->columns - 1),
                ['column' => $column, 'max' => $gameState->columns - 1]
            );
        }

        // Check if bottom disc exists (bottom row is rows - 1)
        $bottomRow = $gameState->rows - 1;
        $bottomDisc = $gameState->getDiscAt($bottomRow, $column);
        if ($bottomDisc === null) {
            return ValidationResult::invalid(
                'NO_DISC_AT_BOTTOM',
                sprintf('No disc at bottom of column %d', $column),
                ['column' => $column]
            );
        }

        // Check if bottom disc belongs to current player
        if ($bottomDisc !== $gameState->currentPlayerUlid) {
            return ValidationResult::invalid(
                'NOT_YOUR_DISC',
                'You can only pop out your own discs',
                ['column' => $column, 'disc_owner' => $bottomDisc]
            );
        }

        return ValidationResult::valid();
    }

    /**
     * Apply a pop_out action to the game state.
     * Returns a new immutable game state with the disc popped and column shifted.
     */
    protected function applyPopOut(GameState $gameState, PopOut $action): GameState
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
}
