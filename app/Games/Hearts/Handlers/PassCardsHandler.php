<?php

declare(strict_types=1);

namespace App\Games\Hearts\Handlers;

use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\ValidationResult;
use App\GameEngine\Actions\PassCards;
use App\Games\Hearts\HeartsTable;
use App\Games\Hearts\Enums\HeartsActionError;
use App\Enums\GameErrorCode;

class PassCardsHandler implements GameActionHandlerInterface
{
    public function validate(object $state, object $action): ValidationResult
    {
        if (! ($state instanceof HeartsTable)) {
             return ValidationResult::invalid(GameErrorCode::INVALID_STATE->value, 'State must be Hearts HeartsTable');
        }
        if (! ($action instanceof PassCards)) {
             return ValidationResult::invalid(GameErrorCode::INVALID_ACTION_TYPE->value, 'Action must be PassCards');
        }
        
        // Check count
        if (count($action->cards) !== 3) {
             return ValidationResult::invalid(HeartsActionError::INVALID_PASS_COUNT->value, 'Must pass exactly 3 cards');
        }

        return ValidationResult::valid();
    }

    public function apply(object $state, object $action): object
    {
        // Coordinated action - state doesn't change until all players submit
        return $state;
    }
    
    public function getAvailableOptions(object $state, string $playerUlid): array
    {
        return [];
    }
}
