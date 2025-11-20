<?php

declare(strict_types=1);

namespace App\Games\Hearts;

/**
 * Player state for Hearts games.
 *
 * Contains player-specific information for a Hearts game instance.
 * Immutable - use withX() methods to create modified copies.
 */
class HeartsPlayer
{
    /**
     * Create a new player state.
     *
     * @param  string  $ulid  Player's ULID
     * @param  int  $position  Player's fixed seat at the table (1-4)
     * @param  int  $score  Player's total score across all rounds
     * @param  int  $roundScore  Points taken in the current round
     */
    public function __construct(
        public readonly string $ulid,
        public readonly int $position,
        public readonly int $score,
        public readonly int $roundScore = 0,
    ) {}

    /**
     * Create player state from array.
     *
     * @param  array<string, mixed>  $data  Serialized player data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ulid: $data['ulid'],
            position: $data['position'],
            score: $data['score'],
            roundScore: $data['roundScore'] ?? 0,
        );
    }

    /**
     * Convert to array for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ulid' => $this->ulid,
            'position' => $this->position,
            'score' => $this->score,
            'roundScore' => $this->roundScore,
        ];
    }

    /**
     * Create a copy with updated score.
     *
     * @param  int  $score  New total score
     * @return self New instance with updated score
     */
    public function withScore(int $score): self
    {
        return new self(
            ulid: $this->ulid,
            position: $this->position,
            score: $score,
            roundScore: $this->roundScore,
        );
    }

    /**
     * Create a copy with updated round score.
     *
     * @param  int  $roundScore  New round score
     * @return self New instance with updated round score
     */
    public function withRoundScore(int $roundScore): self
    {
        return new self(
            ulid: $this->ulid,
            position: $this->position,
            score: $this->score,
            roundScore: $roundScore,
        );
    }

    /**
     * Create a copy with added points to round score.
     *
     * @param  int  $points  Points to add
     * @return self New instance with updated round score
     */
    public function withAddedRoundPoints(int $points): self
    {
        return $this->withRoundScore($this->roundScore + $points);
    }
}
