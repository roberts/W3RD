<?php

declare(strict_types=1);

namespace App\Games\Hearts\Modes;

use App\Games\Hearts\HeartsArbiter;
use App\Games\Hearts\HeartsConfig;
use App\Games\Hearts\HeartsProtocol;
use App\Games\Hearts\HeartsReporter;

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

    protected function getArbiter(): HeartsArbiter
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
