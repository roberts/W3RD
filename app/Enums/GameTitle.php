<?php

namespace App\Enums;

enum GameTitle: string
{
    case VALIDATE_FOUR = 'validate-four';
    case CHECKERS = 'checkers';
    case HEARTS = 'hearts';
    case SPADES = 'spades';

    public function label(): string
    {
        return match ($this) {
            self::VALIDATE_FOUR => 'Validate Four',
            self::CHECKERS => 'Checkers',
            self::HEARTS => 'Hearts',
            self::SPADES => 'Spades',
        };
    }

    public function maxPlayers(): int
    {
        return match ($this) {
            self::VALIDATE_FOUR => 2,
            self::CHECKERS => 2,
            self::HEARTS => 4,
            self::SPADES => 4,
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        return self::tryFrom($slug);
    }

    public function slug(): string
    {
        return $this->value;
    }
}
