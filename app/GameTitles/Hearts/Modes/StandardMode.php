<?php

declare(strict_types=1);

namespace App\GameTitles\Hearts\Modes;

use App\GameTitles\Hearts\HeartsArbiter;
use App\GameTitles\Hearts\HeartsConfig;
use App\GameTitles\Hearts\HeartsProtocol;
use App\GameTitles\Hearts\HeartsReporter;

/**
 * Standard Hearts mode implementation.
 *
 * Implements standard 4-player Hearts rules.
 */
class StandardMode extends HeartsProtocol
{
    protected function getGameConfig(): HeartsConfig
    {
        return new HeartsConfig;
    }

    public function getArbiter(): HeartsArbiter
    {
        return new HeartsArbiter;
    }

    protected function getReporter(): HeartsReporter
    {
        return new HeartsReporter;
    }

    public static function getRules(): array
    {
        $baseRules = parent::getRules();

        $standardRules = [
            'name' => 'Standard',
            'description' => 'Classic 4-player Hearts with passing and shooting the moon.',
        ];

        $baseRules['description'] = $standardRules['description'];
        $baseRules['name'] = $standardRules['name'];

        return $baseRules;
    }
}
