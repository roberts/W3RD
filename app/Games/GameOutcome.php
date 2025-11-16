<?php

declare(strict_types=1);

namespace App\Games;

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
 *     reason: 'four_in_a_row'
 * );
 *
 * // Draw
 * return new GameOutcome(
 *     isFinished: true,
 *     isDraw: true,
 *     reason: 'board_full'
 * );
 *
 * // Scoring game (Hearts)
 * return new GameOutcome(
 *     isFinished: true,
 *     winnerUlid: $lowestScorePlayerUlid,
 *     rankings: ['player1', 'player3', 'player2', 'player4'],
 *     scores: ['player1' => 26, 'player2' => 78, 'player3' => 45, 'player4' => 52],
 *     reason: 'game_complete'
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
     * @param  bool  $isDraw  Whether the game ended in a draw
     * @param  array<int, string>  $rankings  Array of player ULIDs in finishing order (1st to last)
     * @param  array<string, int|float>  $scores  Map of player ULID to final score
     * @param  string|null  $reason  Machine-readable reason for end (e.g., 'four_in_a_row', 'timeout', 'forfeit')
     */
    public function __construct(
        public readonly bool $isFinished,
        public readonly ?string $winnerUlid = null,
        public readonly bool $isDraw = false,
        public readonly array $rankings = [],
        public readonly array $scores = [],
        public readonly ?string $reason = null,
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
     * @param  string|null  $reason  Machine-readable reason for the win
     */
    public static function win(string $winnerUlid, ?string $reason = null): self
    {
        return new self(
            isFinished: true,
            winnerUlid: $winnerUlid,
            reason: $reason
        );
    }

    /**
     * Create an outcome for a draw.
     *
     * @param  string|null  $reason  Machine-readable reason for the draw
     */
    public static function draw(?string $reason = null): self
    {
        return new self(
            isFinished: true,
            isDraw: true,
            reason: $reason
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
            'is_draw' => $this->isDraw,
            'rankings' => $this->rankings,
            'scores' => $this->scores,
            'reason' => $this->reason,
        ];
    }
}
