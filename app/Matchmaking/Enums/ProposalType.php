<?php

namespace App\Matchmaking\Enums;

enum ProposalType: string
{
    case REMATCH = 'rematch';
    case CASUAL = 'casual';
    case TOURNAMENT = 'tournament';

    public function label(): string
    {
        return match ($this) {
            self::REMATCH => 'Rematch',
            self::CASUAL => 'Casual',
            self::TOURNAMENT => 'Tournament',
        };
    }
}
