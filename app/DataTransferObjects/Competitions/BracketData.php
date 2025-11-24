<?php

namespace App\DataTransferObjects\Competitions;

use App\Models\Competitions\Tournament;
use Spatie\LaravelData\Data;

class BracketData extends Data
{
    public function __construct(
        public string $tournament_ulid,
        public string $format,
        /** @var array<int, mixed> */
        public array $rounds,
        public int $current_round,
    ) {}

    public static function fromTournament(Tournament $tournament): self
    {
        $bracketData = $tournament->bracket_data ?? [
            'format' => $tournament->format,
            'rounds' => [],
            'current_round' => 0,
        ];

        return new self(
            tournament_ulid: $tournament->ulid,
            format: $bracketData['format'] ?? $tournament->format,
            rounds: $bracketData['rounds'] ?? [],
            current_round: $bracketData['current_round'] ?? 0,
        );
    }
}
