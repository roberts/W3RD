<?php

namespace App\Enums;

enum ActionType: string
{
    case DROP_PIECE = 'drop_piece';
    case POP_OUT = 'pop_out';
    case MOVE_PIECE = 'move_piece';
    case JUMP_PIECE = 'jump_piece';
    case DOUBLE_JUMP_PIECE = 'double_jump_piece';
    case TRIPLE_JUMP_PIECE = 'triple_jump_piece';
    case PLAY_CARD = 'play_card';
    case PASS_CARDS = 'pass_cards';
    case CLAIM_REMAINING_TRICKS = 'claim_remaining_tricks';
    case PASS = 'pass';
    case DRAW_CARD = 'draw_card';
    case BID = 'bid';
    case DEAL_CARDS = 'deal_cards';

    public function label(): string
    {
        return match ($this) {
            self::DROP_PIECE => 'Drop Piece',
            self::POP_OUT => 'Pop Out',
            self::MOVE_PIECE => 'Move Piece',
            self::JUMP_PIECE => 'Jump Piece',
            self::DOUBLE_JUMP_PIECE => 'Double Jump Piece',
            self::TRIPLE_JUMP_PIECE => 'Triple Jump Piece',
            self::PLAY_CARD => 'Play Card',
            self::PASS_CARDS => 'Pass Cards',
            self::CLAIM_REMAINING_TRICKS => 'Claim Remaining Tricks',
            self::PASS => 'Pass',
            self::DRAW_CARD => 'Draw Card',
            self::BID => 'Bid',
            self::DEAL_CARDS => 'Deal Cards',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DROP_PIECE => 'Place a piece on the board (e.g., Connect Four)',
            self::POP_OUT => 'Remove a piece from the bottom of a column (PopOut mode)',
            self::MOVE_PIECE => 'Move a piece on the board (e.g., Checkers)',
            self::JUMP_PIECE => 'Jump over an opponent piece to capture it (Checkers)',
            self::DOUBLE_JUMP_PIECE => 'Jump over two opponent pieces in sequence (Checkers)',
            self::TRIPLE_JUMP_PIECE => 'Jump over three opponent pieces in sequence (Checkers)',
            self::PLAY_CARD => 'Play a card from hand (e.g., Hearts, Spades)',
            self::PASS_CARDS => 'Pass cards to another player (e.g., Hearts passing phase)',
            self::CLAIM_REMAINING_TRICKS => 'Claim all remaining tricks when holding all winning cards (Hearts)',
            self::PASS => 'Skip turn or pass',
            self::DRAW_CARD => 'Draw a card from the deck',
            self::BID => 'Place a bid',
            self::DEAL_CARDS => 'Deal cards to players',
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
