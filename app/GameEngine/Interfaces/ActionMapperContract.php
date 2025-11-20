<?php

declare(strict_types=1);

namespace App\GameEngine\Interfaces;

/**
 * Contract for action mappers.
 *
 * Each game title should have its own action mapper that implements this interface,
 * allowing the controller to create actions dynamically based on the game type.
 */
interface ActionMapperContract
{
    /**
     * Create an action DTO from request data.
     *
     * @param  string  $actionType  The type of action (e.g., 'drop_piece', 'move_piece', 'play_card')
     * @param  array<string, mixed>  $data  The action data from the request
     * @return GameActionContract The action DTO
     *
     * @throws \InvalidArgumentException If action type is unknown or data is invalid
     */
    public static function create(string $actionType, array $data): GameActionContract;

    /**
     * Validate that the action type exists and has required fields.
     *
     * @param  string  $actionType  The type of action to validate
     * @param  array<string, mixed>  $data  The action data to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate(string $actionType, array $data): bool;

    /**
     * Get all supported action types for this game.
     *
     * @return array<string> Array of action type identifiers
     */
    public static function getSupportedActionTypes(): array;
}
