<?php

namespace App\DataTransferObjects\Games;

use Spatie\LaravelData\Data;

class GameTimerData extends Data
{
    public function __construct(
        public int $turn_number,
        public array $current_player,
        public bool $is_your_turn,
        public ?string $phase,
        public ?array $timeout,
    ) {}

    public static function create(
        int $turnNumber,
        array $currentPlayer,
        bool $isYourTurn,
        ?string $phase = null,
        ?array $timeout = null
    ): self {
        return new self(
            turn_number: $turnNumber,
            current_player: $currentPlayer,
            is_your_turn: $isYourTurn,
            phase: $phase,
            timeout: $timeout,
        );
    }
}
