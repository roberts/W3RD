<?php

namespace App\Enums;

use App\Enums\GameAttributes\GameDynamic;

enum GameTitle: string
{
    case CONNECT_FOUR = 'connect-four';
    case CHECKERS = 'checkers';
    case HEARTS = 'hearts';
    case SPADES = 'spades';

    public function getDynamic(): GameDynamic
    {
        return match ($this) {
            self::CONNECT_FOUR => GameDynamic::ONE_VS_ONE,
            self::CHECKERS => GameDynamic::ONE_VS_ONE,
            self::HEARTS => GameDynamic::FREE_FOR_ALL,
            self::SPADES => GameDynamic::TEAM_BASED,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::CONNECT_FOUR => 'Connect Four',
            self::CHECKERS => 'Checkers',
            self::HEARTS => 'Hearts',
            self::SPADES => 'Spades',
        };
    }

    public function maxPlayers(): int
    {
        return match ($this) {
            self::CONNECT_FOUR => 2,
            self::CHECKERS => 2,
            self::HEARTS => 4,
            self::SPADES => 4,
        };
    }

    public function minPlayers(): int
    {
        return match ($this) {
            self::CONNECT_FOUR => 2,
            self::CHECKERS => 2,
            self::HEARTS => 4,
            self::SPADES => 4,
        };
    }

    public function requiresExactPlayerCount(): bool
    {
        return match ($this) {
            self::CONNECT_FOUR => true,
            self::CHECKERS => true,
            self::HEARTS => true,
            self::SPADES => true,
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

    public function isTrademarked(): bool
    {
        return match ($this) {
            self::CONNECT_FOUR => true,
            self::CHECKERS => false,
            self::HEARTS => false,
            self::SPADES => false,
        };
    }

    public function trademarkOwner(): ?string
    {
        return match ($this) {
            self::CONNECT_FOUR => 'Hasbro',
            self::CHECKERS => null,
            self::HEARTS => null,
            self::SPADES => null,
        };
    }

    public function alternateName(): ?string
    {
        return match ($this) {
            self::CONNECT_FOUR => 'Four in a Row',
            self::CHECKERS => 'Draughts',
            self::HEARTS => null,
            self::SPADES => null,
        };
    }
}
