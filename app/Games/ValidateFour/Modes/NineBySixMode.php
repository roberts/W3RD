<?php

namespace App\Games\ValidateFour\Modes;

use App\Exceptions\InvalidGameConfigurationException;
use App\Games\ValidateFour\ValidateFourProtocol;
use App\Games\ValidateFour\Configs\StandardConfig;
use App\Games\ValidateFour\ValidateFourBoard;
use App\Games\ValidateFour\ValidateFourReporter;
use App\Games\ValidateFour\ValidateFourArbiter;
use App\Games\ValidateFour\ValidateFourConfig;

class NineBySixMode extends ValidateFourProtocol
{
    protected function getGameConfig(): ValidateFourConfig
    {
        return new ValidateFourConfig(
            stateConfig: ['columns' => 9, 'rows' => 6, 'connectCount' => 4]
        );
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

        $modeRules = [
            'name' => 'Nine by Six',
            'description' => 'A wider 9x6 board for a different strategic challenge.',
            'sections' => [
                [
                    'title' => 'Board & Objective',
                    'content' => <<<'MARKDOWN'
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
