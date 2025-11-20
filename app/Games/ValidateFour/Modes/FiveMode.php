<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\ValidateFourArbiter;
use App\Games\ValidateFour\ValidateFourConfig;
use App\Games\ValidateFour\ValidateFourProtocol;
use App\Games\ValidateFour\ValidateFourReporter;

class FiveMode extends ValidateFourProtocol
{
    protected function getGameConfig(): ValidateFourConfig
    {
        return new ValidateFourConfig(
            stateConfig: ['columns' => 9, 'rows' => 7, 'connectCount' => 5]
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
