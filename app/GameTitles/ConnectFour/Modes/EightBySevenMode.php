<?php

namespace App\GameTitles\ConnectFour\Modes;

use App\GameTitles\ConnectFour\ConnectFourArbiter;
use App\GameTitles\ConnectFour\ConnectFourConfig;
use App\GameTitles\ConnectFour\ConnectFourProtocol;
use App\GameTitles\ConnectFour\ConnectFourReporter;

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

    /**
     * @return array<string, mixed>
     */
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
