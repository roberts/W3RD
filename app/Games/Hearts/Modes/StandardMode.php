<?php

declare(strict_types=1);

namespace App\Games\Hearts\Modes;

use App\Games\Hearts\BaseHearts;

/**
 * Standard Hearts mode implementation.
 *
 * Implements standard 4-player Hearts rules.
 */
class StandardMode extends BaseHearts
{
    /**
     * Returns the complete rules for the Standard mode.
     */
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