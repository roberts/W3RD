<?php

declare(strict_types=1);

namespace App\GameEngine;

use App\Enums\OutcomeType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Result of checking game end conditions.
 *
 * Provides flexible outcome representation supporting:
 * - Simple win/loss (Connect Four, Chess)
 * - Draws and stalemates
 * - Scoring systems (Hearts: lowest score wins)
 * - Rankings (multiplayer games)
 * - Reasons (checkmate, timeout, forfeit)
 *
 * Example usage:
 * ```php
 * // Simple win
 * GameOutcome::from([
 *     'isFinished' => true,
 *     'winnerUlid' => $playerUlid,
 *     'type' => OutcomeType::WIN,
 *     'details' => ['reason' => 'four_in_a_row']
 * ]);
 *
 * // Draw
 * GameOutcome::from([
 *     'isFinished' => true,
 *     'type' => OutcomeType::DRAW,
 *     'details' => ['reason' => 'board_full']
 * ]);
 * ```
 */
#[MapName(SnakeCaseMapper::class)]
class GameOutcome extends Data
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
        public bool $isFinished,
        public ?string $winnerUlid = null,
        public ?int $winnerPosition = null,
        public ?OutcomeType $type = null,
        public array $details = [],
        public mixed $gameState = null,
    ) {}

    /**
     * Create an outcome for a game still in progress.
     */
    public static function inProgress(mixed $gameState = null): self
    {
        return new self(isFinished: false, gameState: $gameState);
    }

    /**
     * Get the game state.
     */
    public function getGameState(): mixed
    {
        return $this->gameState;
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
}
