<?php

namespace App\Enums;

enum ActionType: string
{
    case DROP_PIECE = 'drop_piece';
    case MOVE_PIECE = 'move_piece';
    case PLAY_CARD = 'play_card';
    case PASS = 'pass';
    case DRAW_CARD = 'draw_card';
    case BID = 'bid';

    public function label(): string
    {
        return match($this) {
            self::DROP_PIECE => 'Drop Piece',
            self::MOVE_PIECE => 'Move Piece',
            self::PLAY_CARD => 'Play Card',
            self::PASS => 'Pass',
            self::DRAW_CARD => 'Draw Card',
            self::BID => 'Bid',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::DROP_PIECE => 'Place a piece on the board (e.g., Connect Four)',
            self::MOVE_PIECE => 'Move a piece on the board (e.g., Checkers)',
            self::PLAY_CARD => 'Play a card from hand (e.g., Hearts, Spades)',
            self::PASS => 'Skip turn or pass',
            self::DRAW_CARD => 'Draw a card from deck',
            self::BID => 'Place a bid (e.g., Spades bidding)',
        };
    }

    public static function fromValue(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }
        return null;
    }
}
