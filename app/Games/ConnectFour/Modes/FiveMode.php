<?php

namespace App\Games\ConnectFour\Modes;

use App\Games\ConnectFour\ConnectFourArbiter;
use App\Games\ConnectFour\ConnectFourConfig;
use App\Games\ConnectFour\ConnectFourProtocol;
use App\Games\ConnectFour\ConnectFourReporter;

class FiveMode extends ConnectFourProtocol
{
    protected function getGameConfig(): ConnectFourConfig
    {
        return new ConnectFourConfig(
            stateConfig: ['columns' => 9, 'rows' => 7, 'connectCount' => 5]
        );
    }

    public function getArbiter(): ConnectFourArbiter
    {
        return new ConnectFourArbiter;
    }

    protected function getReporter(): ConnectFourReporter
    {
        return new ConnectFourReporter;
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
