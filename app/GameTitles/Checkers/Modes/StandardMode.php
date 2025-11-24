<?php

declare(strict_types=1);

namespace App\GameTitles\Checkers\Modes;

use App\Enums\GameAttributes\GameTimer;
use App\GameTitles\Checkers\CheckersArbiter;
use App\GameTitles\Checkers\CheckersConfig;
use App\GameTitles\Checkers\CheckersProtocol;
use App\GameTitles\Checkers\CheckersReporter;

/**
 * Standard Checkers mode implementation.
 *
 * Implements the standard American Checkers (English Draughts) rules.
 */
class StandardMode extends CheckersProtocol
{
    public static function getTimer(): GameTimer
    {
        return GameTimer::FORFEIT;
    }

    protected function getGameConfig(): CheckersConfig
    {
        return new CheckersConfig;
    }

    public function getArbiter(): CheckersArbiter
    {
        return new CheckersArbiter;
    }

    protected function getReporter(): CheckersReporter
    {
        return new CheckersReporter;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getRules(): array
    {
        $baseRules = parent::getRules();

        $standardRules = [
            'name' => 'Standard',
            'description' => 'Classic American Checkers (English Draughts) on an 8x8 board.',
        ];

        $baseRules['description'] = $standardRules['description'];
        $baseRules['name'] = $standardRules['name'];

        return $baseRules;
    }
}
