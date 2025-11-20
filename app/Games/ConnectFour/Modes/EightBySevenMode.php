<?php

namespace App\Games\ConnectFour\Modes;

use App\Games\ConnectFour\ConnectFourArbiter;
use App\Games\ConnectFour\ConnectFourConfig;
use App\Games\ConnectFour\ConnectFourProtocol;
use App\Games\ConnectFour\ConnectFourReporter;

class EightBySevenMode extends ConnectFourProtocol
{
    protected function getGameConfig(): ConnectFourConfig
    {
        return new ConnectFourConfig(
            stateConfig: ['columns' => 8, 'rows' => 7, 'connectCount' => 4]
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
