<?php

declare(strict_types=1);

namespace App\Games\Checkers\Modes;

use App\Games\Checkers\BaseCheckers;

/**
 * Standard Checkers mode implementation.
 *
 * Implements the standard American Checkers (English Draughts) rules.
 */
class StandardMode extends BaseCheckers
{
    /**
     * Returns the complete rules for the Standard mode.
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
