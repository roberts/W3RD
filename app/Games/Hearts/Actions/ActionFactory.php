<?php

declare(strict_types=1);

namespace App\Games\Hearts\Actions;

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
     */
    public static function create(string $type, array $data): object
    {
        return match ($type) {
            'pass_cards' => new PassCards(
                cards: $data['cards'],
            ),
            'play_card' => new PlayCard(
                card: $data['card'],
            ),
            'claim_remaining_tricks' => new ClaimRemainingTricks,
            default => throw new \InvalidArgumentException("Unknown action type: {$type}"),
        };
    }
}
