<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\BaseValidateFour;
use App\Games\ValidateFour\GameState;

class FiveMode extends BaseValidateFour
{
    /**
     * Create initial game state for Connect Five mode.
     *
     * @param  string  ...$playerUlids  Player ULIDs (must be exactly 2)
     * @return GameState
     *
     * @throws \InvalidArgumentException If not exactly 2 players provided
     */
    public function createInitialState(string ...$playerUlids): object
    {
        if (count($playerUlids) !== 2) {
            throw new \InvalidArgumentException('Validate Five requires exactly 2 players');
        }

        return GameState::createNew(
            playerOneUlid: $playerUlids[0],
            playerTwoUlid: $playerUlids[1],
            columns: 9,
            rows: 7,
            connectCount: 5
        );
    }

    /**
     * Returns the complete rules for the Connect Five mode.
     */
    public static function getRules(): array
    {
        $baseRules = parent::getRules();

        $modeRules = [
            'name' => 'Connect Five',
            'description' => 'The goal is to connect five discs in a row on a larger 9x7 board.',
            'sections' => [
                [
                    'title' => 'Board & Objective',
                    'content' => <<<'MARKDOWN'
                    *   **Board size:** 9 columns × 7 rows.
                    *   **Objective:** Connect **five** of your discs in a row.
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
