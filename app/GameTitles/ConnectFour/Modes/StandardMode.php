<?php

namespace App\GameTitles\ConnectFour\Modes;

use App\GameTitles\ConnectFour\ConnectFourArbiter;
use App\GameTitles\ConnectFour\ConnectFourConfig;
use App\GameTitles\ConnectFour\ConnectFourProtocol;
use App\GameTitles\ConnectFour\ConnectFourReporter;

class StandardMode extends ConnectFourProtocol
{
    // StandardMode uses all the default logic from ConnectFourProtocolMode
    // No additional customization needed for the standard 7x6, connect-4 game

    protected function getGameConfig(): ConnectFourConfig
    {
        return new ConnectFourConfig;
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
