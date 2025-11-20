<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\ValidateFourArbiter;
use App\Games\ValidateFour\ValidateFourConfig;
use App\Games\ValidateFour\ValidateFourProtocol;
use App\Games\ValidateFour\ValidateFourReporter;

class EightBySevenMode extends ValidateFourProtocol
{
    protected function getGameConfig(): ValidateFourConfig
    {
        return new ValidateFourConfig(
            stateConfig: ['columns' => 8, 'rows' => 7, 'connectCount' => 4]
        );
    }

    public function getArbiter(): ValidateFourArbiter
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
