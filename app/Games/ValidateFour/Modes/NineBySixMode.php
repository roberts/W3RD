<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\BaseValidateFour;
use App\Games\ValidateFour\GameState;

class NineBySixMode extends BaseValidateFour
{
    /**
     * Create initial game state for 9x6 mode.
     *
     * @param string ...$playerUlids Player ULIDs (must be exactly 2)
     * @return GameState
     * @throws \InvalidArgumentException If not exactly 2 players provided
     */
    public function createInitialState(string ...$playerUlids): object
    {
        if (count($playerUlids) !== 2) {
            throw new \InvalidArgumentException('Validate Four (9x6 Mode) requires exactly 2 players');
        }

        return GameState::createNew(
            playerOneUlid: $playerUlids[0],
            playerTwoUlid: $playerUlids[1],
            columns: 9,
            rows: 6,
            connectCount: 4
        );
    }

    /**
     * Returns the complete rules for the 9x6 mode.
     *
     * @return array
     */
    public static function getRules(): array
    {
        $baseRules = parent::getRules();

        $modeRules = [
            'name' => 'Nine by Six',
            'description' => 'A wider 9x6 board for a different strategic challenge.',
            'sections' => [
                [
                    'title' => 'Board & Objective',
                    'content' => <<<MARKDOWN
                    *   **Board size:** 9 columns × 6 rows.
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
