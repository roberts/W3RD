<?php

declare(strict_types=1);

namespace App\GameEngine\Interfaces;

use App\GameEngine\ValidationResult;

interface GameActionHandlerInterface
{
    /**
     * Validate if the action is allowed in the current state.
     */
    public function validate(object $state, object $action): ValidationResult;

    /**
     * Apply the action to the state and return the new state.
     */
    public function apply(object $state, object $action): object;

    /**
     * Returns the valid parameters for this action for the given player.
     * e.g. DropPieceHandler returns ['columns' => [0, 1, 2, 4]]
     *
     * @return array<string, mixed>
     */
    public function getAvailableOptions(object $state, string $playerUlid): array;
}
