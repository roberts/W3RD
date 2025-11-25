<?php

namespace App\GameTitles\Contracts;

interface GameTitleContract
{
    /**
     * Get the unique string identifier for this game title (e.g., "checkers").
     */
    public static function getIdentifier(): string;

    /**
     * Get a list of available GameModeContract implementation classes.
     */
    public function getAvailableModes(): array;

    /**
     * Create the initial game state for a new match.
     *
     * @param  string  ...$playerUlids  The ULIDs of the players joining the game.
     * @return object The initial GameState object.
     */
    public function createInitialState(string ...$playerUlids): object;

    /**
     * Get the structured rules for this game.
     */
    public static function getRules(): array;
}
