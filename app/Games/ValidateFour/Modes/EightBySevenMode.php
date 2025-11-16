<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\BaseValidateFour;
use App\Games\ValidateFour\GameState;

class EightBySevenMode extends BaseValidateFour
{
    /**
     * Create initial game state for 8x7 mode.
     *
     * @param  string  ...$playerUlids  Player ULIDs (must be exactly 2)
     * @return GameState
     *
     * @throws \InvalidArgumentException If not exactly 2 players provided
     */
    public function createInitialState(string ...$playerUlids): object
    {
        if (count($playerUlids) !== 2) {
            throw new \InvalidArgumentException('Validate Four (8x7 Mode) requires exactly 2 players');
        }

        return GameState::createNew(
            playerOneUlid: $playerUlids[0],
            playerTwoUlid: $playerUlids[1],
            columns: 8,
            rows: 7,
            connectCount: 4
        );
    }

    /**
     * Returns the complete rules for the 8x7 mode.
     */
    public static function getRules(): array
    {
        $baseRules = parent::getRules();

        $modeRules = [
            'name' => 'Eight by Seven',
            'description' => 'A larger 8x7 board for a more complex game.',
            'sections' => [
                [
                    'title' => 'Board & Objective',
                    'content' => <<<'MARKDOWN'
                    *   **Board size:** 8 columns × 7 rows.
                    *   **Objective:** Connect four of your discs in a row.
                    MARKDOWN,
                ],
            ],
        ];

        $baseRules['sections'] = array_merge($baseRules['sections'], $modeRules['sections']);
        $baseRules['description'] = $modeRules['description'];
        $baseRules['name'] = $modeRules['name'];

        return $baseRules;
    }
}
