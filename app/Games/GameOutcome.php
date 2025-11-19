<?php

declare(strict_types=1);

namespace App\Games;

use App\Enums\OutcomeType;

/**
 * Result of checking game end conditions.
 *
 * Provides flexible outcome representation supporting:
 * - Simple win/loss (Validate Four, Chess)
 * - Draws and stalemates
 * - Scoring systems (Hearts: lowest score wins)
 * - Rankings (multiplayer games)
 * - Reasons (checkmate, timeout, forfeit)
 *
 * Example usage:
 * ```php
 * // Simple win
 * return new GameOutcome(
 *     isFinished: true,
 *     winnerUlid: $playerUlid,
 *     type: OutcomeType::WIN,
 *     details: ['reason' => 'four_in_a_row']
 * );
 *
 * // Draw
 * return new GameOutcome(
 *     isFinished: true,
 *     type: OutcomeType::DRAW,
 *     details: ['reason' => 'board_full']
 * );
 * ```
 */
class GameOutcome
{
    /**
     * Create a new game outcome.
     *
     * @param  bool  $isFinished  Whether the game has ended
     * @param  string|null  $winnerUlid  ULID of the winning player, or null if no winner yet
     * @param  int|null  $winnerPosition  Position of the winning player (1-based)
     * @param  OutcomeType|null  $type  The type of outcome (win, draw, etc.)
     * @param  array  $details  Flexible game-specific details (reason, scores, rankings, etc.)
     */
    public function __construct(
        public readonly bool $isFinished,
        public readonly ?string $winnerUlid = null,
        public readonly ?int $winnerPosition = null,
        public readonly ?OutcomeType $type = null,
        public readonly array $details = [],
    ) {}

    /**
     * Create an outcome for a game still in progress.
     */
    public static function inProgress(): self
    {
        return new self(isFinished: false);
    }

    /**
     * Create an outcome for a simple win.
     *
     * @param  string  $winnerUlid  ULID of the winning player
     * @param  int|null  $winnerPosition  Position of the winning player
     * @param  string|null  $reason  Machine-readable reason for the win
     * @param  array  $additionalDetails  Additional details to merge into the details array
     */
    public static function win(string $winnerUlid, ?int $winnerPosition = null, ?string $reason = null, array $additionalDetails = []): self
    {
        $details = $additionalDetails;
        if ($reason) {
            $details['reason'] = $reason;
        }

        return new self(
            isFinished: true,
            winnerUlid: $winnerUlid,
            winnerPosition: $winnerPosition,
            type: OutcomeType::WIN,
            details: $details
        );
    }

    /**
     * Create an outcome for a draw.
     *
     * @param  string|null  $reason  Machine-readable reason for the draw
     * @param  array  $additionalDetails  Additional details to merge into the details array
     */
    public static function draw(?string $reason = null, array $additionalDetails = []): self
    {
        $details = $additionalDetails;
        if ($reason) {
            $details['reason'] = $reason;
        }

        return new self(
            isFinished: true,
            type: OutcomeType::DRAW,
            details: $details
        );
    }

    /**
     * Convert to array for API responses and storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_finished' => $this->isFinished,
            'winner_ulid' => $this->winnerUlid,
            'winner_position' => $this->winnerPosition,
            'type' => $this->type?->value,
            'details' => $this->details,
        ];
    }
}
