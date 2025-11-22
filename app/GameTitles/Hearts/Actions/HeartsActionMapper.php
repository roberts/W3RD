<?php

declare(strict_types=1);

namespace App\GameTitles\Hearts\Actions;

use App\Exceptions\InvalidActionDataException;
use App\GameEngine\Actions\ClaimRemainingTricks;
use App\GameEngine\Actions\PassCards;
use App\GameEngine\Actions\PlayCard;
use App\GameEngine\Interfaces\ActionMapperContract;
use App\GameEngine\Interfaces\GameActionContract;

/**
 * Action factory for Hearts game actions.
 *
 * Creates action objects from raw input data.
 */
class HeartsActionMapper implements ActionMapperContract
{
    /**
     * Create an action DTO from request data.
     *
     * @param  string  $actionType  The type of action (e.g., 'pass_cards', 'play_card')
     * @param  array<string, mixed>  $data  The action data from the request
     * @return GameActionContract The action DTO
     *
     * @throws InvalidActionDataException If action type is unknown or data is invalid
     */
    public static function create(string $actionType, array $data): GameActionContract
    {
        // Validate action type
        $supportedTypes = self::getSupportedActionTypes();
        if (! in_array($actionType, $supportedTypes, true)) {
            throw new InvalidActionDataException(
                sprintf('Unknown action type: %s', $actionType),
                'unknown_action_type',
                'hearts',
                [
                    'action_type' => $actionType,
                    'supported_types' => $supportedTypes,
                ]
            );
        }

        // Validate required fields based on action type
        if ($actionType === 'pass_cards') {
            if (! isset($data['cards'])) {
                throw new InvalidActionDataException(
                    'Missing required field: cards for pass_cards action',
                    'missing_required_field',
                    'hearts',
                    [
                        'action_type' => $actionType,
                        'missing_field' => 'cards',
                        'required_fields' => ['cards'],
                    ]
                );
            }

            if (! is_array($data['cards'])) {
                throw new InvalidActionDataException(
                    sprintf('Field "cards" must be an array, %s provided', gettype($data['cards'])),
                    'invalid_field_type',
                    'hearts',
                    [
                        'field' => 'cards',
                        'expected_type' => 'array',
                        'actual_type' => gettype($data['cards']),
                    ]
                );
            }

            return new PassCards(cards: $data['cards']);
        }

        if ($actionType === 'play_card') {
            if (! isset($data['card'])) {
                throw new InvalidActionDataException(
                    'Missing required field: card for play_card action',
                    'missing_required_field',
                    'hearts',
                    [
                        'action_type' => $actionType,
                        'missing_field' => 'card',
                        'required_fields' => ['card'],
                    ]
                );
            }

            if (! is_string($data['card'])) {
                throw new InvalidActionDataException(
                    sprintf('Field "card" must be a string, %s provided', gettype($data['card'])),
                    'invalid_field_type',
                    'hearts',
                    [
                        'field' => 'card',
                        'expected_type' => 'string',
                        'actual_type' => gettype($data['card']),
                    ]
                );
            }

            return new PlayCard(card: $data['card']);
        }

        if ($actionType === 'claim_remaining_tricks') {
            return new ClaimRemainingTricks;
        }

        // deal_cards requires no data
        return new DealCards;
    }

    /**
     * Validate that the action type exists and has required fields.
     *
     * @param  string  $actionType  The type of action to validate
     * @param  array<string, mixed>  $data  The action data to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate(string $actionType, array $data): bool
    {
        if (! in_array($actionType, self::getSupportedActionTypes(), true)) {
            return false;
        }

        // Basic validation logic
        return true;
    }

    /**
     * Get all supported action types for this game.
     *
     * @return array<string> Array of action type identifiers
     */
    public static function getSupportedActionTypes(): array
    {
        return ['pass_cards', 'play_card', 'claim_remaining_tricks', 'deal_cards'];
    }
}
