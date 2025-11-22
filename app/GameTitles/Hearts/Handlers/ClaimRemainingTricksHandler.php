<?php

declare(strict_types=1);

namespace App\GameTitles\Hearts\Handlers;

use App\Enums\GameErrorCode;
use App\GameEngine\Actions\ClaimRemainingTricks;
use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\ValidationResult;
use App\GameTitles\Hearts\HeartsTable;

class ClaimRemainingTricksHandler implements GameActionHandlerInterface
{
    public function validate(object $state, object $action): ValidationResult
    {
        if (! ($state instanceof HeartsTable)) {
            return ValidationResult::invalid(GameErrorCode::INVALID_STATE->value, 'State must be Hearts HeartsTable');
        }
        if (! ($action instanceof ClaimRemainingTricks)) {
            return ValidationResult::invalid(GameErrorCode::INVALID_ACTION_TYPE->value, 'Action must be ClaimRemainingTricks');
        }

        return ValidationResult::valid();
    }

    public function apply(object $state, object $action): object
    {
        // Placeholder implementation as per original HeartsProtocol
        return $state;
    }

    public function getAvailableOptions(object $state, string $playerUlid): array
    {
        return [];
    }
}
