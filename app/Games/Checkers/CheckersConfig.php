<?php

declare(strict_types=1);

namespace App\Games\Checkers;

use App\GameEngine\Actions\DoubleJumpPiece;
use App\GameEngine\Actions\JumpPiece;
use App\GameEngine\Actions\MovePiece;
use App\GameEngine\Actions\TripleJumpPiece;
use App\GameEngine\Interfaces\GameConfigContract;
use App\Games\Checkers\Handlers\DoubleJumpPieceHandler;
use App\Games\Checkers\Handlers\JumpPieceHandler;
use App\Games\Checkers\Handlers\MovePieceHandler;
use App\Games\Checkers\Handlers\TripleJumpPieceHandler;

class CheckersConfig implements GameConfigContract
{
    public function getActionRegistry(): array
    {
        return [
            MovePiece::class => [
                'handler' => MovePieceHandler::class,
                'label' => 'Move Piece',
            ],
            JumpPiece::class => [
                'handler' => JumpPieceHandler::class,
                'label' => 'Jump Piece',
            ],
            DoubleJumpPiece::class => [
                'handler' => DoubleJumpPieceHandler::class,
                'label' => 'Double Jump Piece',
            ],
            TripleJumpPiece::class => [
                'handler' => TripleJumpPieceHandler::class,
                'label' => 'Triple Jump Piece',
            ],
        ];
    }

    public function getInitialStateConfig(): array
    {
        return [];
    }

    public function getRulesDescription(): array
    {
        return [
            'title' => 'Checkers (American/English Draughts)',
            'description' => 'Capture all of your opponent\'s pieces or block them from making any legal moves.',
            'sections' => [
                [
                    'title' => 'Setup',
                    'content' => <<<'MARKDOWN'
                    *   8x8 board with alternating light and dark squares.
                    *   Each player starts with 12 pieces placed on the dark squares of the three rows closest to them.
                    *   Red pieces start at the bottom, black pieces at the top.
                    MARKDOWN,
                ],
                [
                    'title' => 'Movement',
                    'content' => <<<'MARKDOWN'
                    *   Players take turns moving one piece per turn.
                    *   Regular pieces move diagonally forward one square to an empty dark square.
                    *   Kings (promoted pieces) can move diagonally forward or backward one square.
                    MARKDOWN,
                ],
                [
                    'title' => 'Captures',
                    'content' => <<<'MARKDOWN'
                    *   Captures are mandatory. If a capture is available, it must be taken.
                    *   Capture by jumping over an opponent's piece to an empty square beyond it.
                    *   Multiple captures can be made in a single turn if available after the first jump.
                    *   Captured pieces are removed from the board.
                    MARKDOWN,
                ],
                [
                    'title' => 'King Promotion',
                    'content' => <<<'MARKDOWN'
                    *   When a piece reaches the opposite end of the board, it is promoted to a King.
                    *   Kings can move and capture both forward and backward.
                    MARKDOWN,
                ],
                [
                    'title' => 'Winning',
                    'content' => <<<'MARKDOWN'
                    *   Win by capturing all of your opponent's pieces.
                    *   Win if your opponent has no legal moves available.
                    MARKDOWN,
                ],
            ],
        ];
    }
}
