<?php

declare(strict_types=1);

namespace App\Games\Hearts\Handlers;

use App\Enums\GameErrorCode;
use App\Enums\GamePhase;
use App\GameEngine\Interfaces\GameActionHandlerInterface;
use App\GameEngine\ValidationResult;
use App\Games\Hearts\Actions\DealCards;
use App\Games\Hearts\Enums\HeartsActionError;
use App\Games\Hearts\HeartsTable;

class DealCardsHandler implements GameActionHandlerInterface
{
    public function validate(object $state, object $action): ValidationResult
    {
        if (! $state instanceof HeartsTable) {
            return ValidationResult::invalid(GameErrorCode::INVALID_STATE->value, 'State must be Hearts HeartsTable');
        }

        if (! $action instanceof DealCards) {
            return ValidationResult::invalid(GameErrorCode::INVALID_ACTION_TYPE->value, 'Action must be DealCards');
        }

        if ($state->phase !== GamePhase::SETUP) {
            return ValidationResult::invalid(HeartsActionError::WRONG_PHASE->value, 'Can only deal cards in SETUP phase');
        }

        // Check if hands are already dealt (not empty)
        $hands = $state->hands;
        $firstHand = reset($hands);
        if (! empty($firstHand)) {
            return ValidationResult::invalid('ALREADY_DEALT', 'Cards have already been dealt');
        }

        return ValidationResult::valid();
    }

    public function apply(object $state, object $action): object
    {
        /** @var HeartsTable $state */
        return $state->dealCards();
    }

    public function getAvailableOptions(object $state, string $playerUlid): array
    {
        return [];
    }
}
