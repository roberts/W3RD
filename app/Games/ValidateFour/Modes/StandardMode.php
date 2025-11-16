<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\BaseValidateFour;

class StandardMode extends BaseValidateFour
{
    // StandardMode uses all the default logic from BaseValidateFourMode
    // No additional customization needed for the standard 7x6, connect-4 game

    /**
     * Returns the complete rules for the Standard mode.
     */
    public static function getRules(): array
    {
        $baseRules = parent::getRules();

        $standardRules = [
            'name' => 'Standard',
            'description' => 'The classic game of Connect 4 on a 7x6 grid.',
            'sections' => [
                [
                    'title' => 'Board & Objective',
                    'content' => <<<'MARKDOWN'
                    *   **Board size:** 7 columns × 6 rows.
                    *   **Objective:** Connect four of your discs in a row.
                    MARKDOWN,
                ],
            ],
        ];

        $baseRules['sections'] = array_merge($baseRules['sections'], $standardRules['sections']);
        $baseRules['description'] = $standardRules['description'];
        $baseRules['name'] = $standardRules['name'];

        return $baseRules;
    }
}
