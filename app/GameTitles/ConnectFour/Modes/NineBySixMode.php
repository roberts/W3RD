<?php

namespace App\GameTitles\ConnectFour\Modes;

use App\GameTitles\ConnectFour\ConnectFourArbiter;
use App\GameTitles\ConnectFour\ConnectFourConfig;
use App\GameTitles\ConnectFour\ConnectFourProtocol;
use App\GameTitles\ConnectFour\ConnectFourReporter;

class NineBySixMode extends ConnectFourProtocol
{
    protected function getGameConfig(): ConnectFourConfig
    {
        return new ConnectFourConfig(
            stateConfig: ['columns' => 9, 'rows' => 6, 'connectCount' => 4]
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
