<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Contract for all game action DTOs.
 *
 * Actions represent player moves/decisions in a game. All action DTOs must
 * implement this interface to ensure they can be stored, validated, and processed
 * consistently across different game types.
 *
 * Examples:
 * - Validate Four: DropDiscAction, PopOutAction
 * - Chess: MoveAction, CastleAction, PromoteAction
 * - Poker: BetAction, FoldAction, CallAction
 */
interface GameActionContract
{
    /**
     * Get the action type identifier.
     *
     * Returns a string identifier for this action type (e.g., 'drop_disc', 'move_piece', 'play_card').
     * This is used for validation, routing, and storage.
     *
     * @return string Action type identifier (snake_case)
     */
    public function getType(): string;

    /**
     * Convert the action to an array for storage.
     *
     * Serializes the action into an associative array suitable for JSON storage
     * in the database. The array should contain all data needed to reconstruct
     * the action later.
     *
     * Example:
     * ```php
     * // DropDiscAction
     * ['column' => 3]
     *
     * // MoveAction for Chess
     * ['from' => 'e2', 'to' => 'e4']
     * ```
     *
     * @return array<string, mixed> Associative array of action data
     */
    public function toArray(): array;
}
