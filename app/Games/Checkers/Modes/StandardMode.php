<?php

declare(strict_types=1);

namespace App\Games\Checkers\Modes;

use App\Games\Checkers\CheckersArbiter;
use App\Games\Checkers\CheckersConfig;
use App\Games\Checkers\CheckersProtocol;
use App\Games\Checkers\CheckersReporter;
use App\Enums\GameAttributes\GameTimer;

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
