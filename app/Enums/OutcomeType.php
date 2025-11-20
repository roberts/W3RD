<?php

namespace App\Enums;

enum OutcomeType: string
{
    case WIN = 'win';
    case DRAW = 'draw';
    case FORFEIT = 'forfeit';
    case TIMEOUT = 'timeout';
    case RESIGNATION = 'resignation';
    case ABANDONED = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::WIN => 'Win',
            self::DRAW => 'Draw',
            self::FORFEIT => 'Forfeit',
            self::TIMEOUT => 'Timeout',
            self::RESIGNATION => 'Resignation',
            self::ABANDONED => 'Abandoned',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::WIN => 'The game ended with a clear winner.',
            self::DRAW => 'The game ended in a tie.',
            self::FORFEIT => 'A player forfeited the game.',
            self::TIMEOUT => 'The game ended due to a player timeout.',
            self::RESIGNATION => 'A player resigned from the game.',
            self::ABANDONED => 'The game was abandoned before completion.',
        };
    }
}
