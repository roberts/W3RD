<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\ValidateFourProtocol;
use App\Games\ValidateFour\Configs\StandardConfig;
use App\Games\ValidateFour\ValidateFourReporter;
use App\Games\ValidateFour\ValidateFourArbiter;
use App\Games\ValidateFour\ValidateFourConfig;

class StandardMode extends ValidateFourProtocol
{
    // StandardMode uses all the default logic from ValidateFourProtocolMode
    // No additional customization needed for the standard 7x6, connect-4 game

    protected function getGameConfig(): ValidateFourConfig
    {
        return new ValidateFourConfig;
    }

    protected function getArbiter(): ValidateFourArbiter
    {
        return new ValidateFourArbiter;
    }

    protected function getReporter(): ValidateFourReporter
    {
        return new ValidateFourReporter;
    }

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
