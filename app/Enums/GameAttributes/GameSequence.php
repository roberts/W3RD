<?php

declare(strict_types=1);

namespace App\Enums\GameAttributes;

/**
 * Defines the flow of action.
 *
 * Tells the client UI when to unlock input controls.
 */
enum GameSequence: string
{
    /**
     * Strict "Player A goes, then Player B goes." No interruptions.
     * Examples: Monopoly, Scrabble, Catan
     */
    case SEQUENTIAL = 'sequential';

    /**
     * All players commit actions secretly; resolution happens all at once.
     * Examples: Rock-Paper-Scissors, 7 Wonders, Texas Hold'em (Pre-flop fold)
     */
    case SIMULTANEOUS = 'simultaneous';

    /**
     * Generally sequential, but opponents can react/interrupt out of turn.
     * Examples: Magic: The Gathering (Instants), D&D (Reactions), Exploding Kittens
     */
    case INTERLEAVED = 'interleaved';

    /**
     * Game mode changes entirely based on the current stage.
     * Examples: Agricola (Harvest Phase), Poker (Betting vs Showdown), Werewolf (Day/Night)
     */
    case PHASE_BASED = 'phase_based';
}
