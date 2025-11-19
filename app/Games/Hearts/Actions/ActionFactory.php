<?php

declare(strict_types=1);

namespace App\Games\Hearts\Actions;

use App\Exceptions\InvalidActionDataException;

/**
 * Action factory for Hearts game actions.
 *
 * Creates action objects from raw input data.
 */
class ActionFactory
{
    /**
     * Create an action from type and data.
     *
     * @param  string  $type  Action type (e.g., 'pass_cards', 'play_card')
     * @param  array<string, mixed>  $data  Action data
     * @return object Action object
     *
     * @throws InvalidActionDataException If action type is unknown or data is invalid
     */
    public static function create(string $type, array $data): object
    {
        // Validate action type
        $supportedTypes = ['pass_cards', 'play_card', 'claim_remaining_tricks'];
        if (! in_array($type, $supportedTypes, true)) {
            throw new InvalidActionDataException(
                sprintf('Unknown action type: %s', $type),
                'unknown_action_type',
                'hearts',
                [
                    'action_type' => $type,
                    'supported_types' => $supportedTypes,
                ]
            );
        }

        // Validate required fields based on action type
        if ($type === 'pass_cards') {
            if (! isset($data['cards'])) {
                throw new InvalidActionDataException(
                    'Missing required field: cards for pass_cards action',
                    'missing_required_field',
                    'hearts',
                    [
                        'action_type' => $type,
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

        if ($type === 'play_card') {
            if (! isset($data['card'])) {
                throw new InvalidActionDataException(
                    'Missing required field: card for play_card action',
                    'missing_required_field',
                    'hearts',
                    [
                        'action_type' => $type,
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

        // claim_remaining_tricks has no required fields
        return new ClaimRemainingTricks;
    }
}
