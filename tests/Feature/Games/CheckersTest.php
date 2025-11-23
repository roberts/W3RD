<?php

declare(strict_types=1);

use App\GameTitles\Checkers\CheckersArbiter;
use App\GameTitles\Checkers\CheckersBoard;
use App\GameTitles\Checkers\Modes\StandardMode;
use App\Models\Games\Game;

describe('Checkers Game Logic', function () {
    test('can create initial game state', function () {
        $game = new Game(['game_state' => []]);
        $checkers = new StandardMode($game);
        $playerOne = 'player-one-ulid';
        $playerTwo = 'player-two-ulid';

        $state = $checkers->createInitialState($playerOne, $playerTwo);

        expect($state)->toBeInstanceOf(CheckersBoard::class)
            ->and($state->currentPlayerUlid)->toBe($playerOne)
            ->and($state->players)->toHaveCount(2)
            ->and($state->players[$playerOne]->color)->toBe('red')
            ->and($state->players[$playerTwo]->color)->toBe('black')
            ->and($state->players[$playerOne]->piecesRemaining)->toBe(12)
            ->and($state->players[$playerTwo]->piecesRemaining)->toBe(12);
    });

    test('board is properly initialized', function () {
        $game = new Game(['game_state' => []]);
        $checkers = new StandardMode($game);
        $state = $checkers->createInitialState('p1', 'p2');

        // Count total pieces on board
        $redPieces = 0;
        $blackPieces = 0;

        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $piece = $state->getPieceAt($row, $col);
                if ($piece !== null) {
                    if ($state->players[$piece['player']]->color === 'red') {
                        $redPieces++;
                    } else {
                        $blackPieces++;
                    }
                }
            }
        }

        expect($redPieces)->toBe(12)
            ->and($blackPieces)->toBe(12);
    });

    test('pieces are placed on correct rows', function () {
        $game = new Game(['game_state' => []]);
        $checkers = new StandardMode($game);
        $state = $checkers->createInitialState('p1', 'p2');

        // Black pieces should be in rows 0-2
        for ($row = 0; $row <= 2; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if (($row + $col) % 2 === 1) {
                    $piece = $state->getPieceAt($row, $col);
                    expect($piece)->not->toBeNull()
                        ->and($state->players[$piece['player']]->color)->toBe('black');
                }
            }
        }

        // Red pieces should be in rows 5-7
        for ($row = 5; $row <= 7; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if (($row + $col) % 2 === 1) {
                    $piece = $state->getPieceAt($row, $col);
                    expect($piece)->not->toBeNull()
                        ->and($state->players[$piece['player']]->color)->toBe('red');
                }
            }
        }

        // Middle rows should be empty
        for ($row = 3; $row <= 4; $row++) {
            for ($col = 0; $col < 8; $col++) {
                expect($state->getPieceAt($row, $col))->toBeNull();
            }
        }
    });

    test('can detect when a player has no pieces left', function () {
        $game = new Game(['game_state' => []]);
        $arbiter = new CheckersArbiter;
        $state = CheckersBoard::createNew('p1', 'p2');

        // Manually set pieces remaining to 0 for player 2
        $state = new CheckersBoard(
            players: [
                'p1' => $state->players['p1'],
                'p2' => $state->players['p2']->withPiecesRemaining(0),
            ],
            currentPlayerUlid: 'p1',
            winnerUlid: null,
            phase: $state->phase,
            status: $state->status,
            board: $state->board,
            isDraw: false,
        );

        $outcome = $arbiter->checkWinCondition($state);

        expect($outcome->isFinished)->toBeTrue()
            ->and($outcome->winnerUlid)->toBe('p1');
    });

    test('game is in progress when both players have pieces', function () {
        $game = new Game(['game_state' => []]);
        $arbiter = new CheckersArbiter;
        $state = CheckersBoard::createNew('p1', 'p2');

        $outcome = $arbiter->checkWinCondition($state);

        expect($outcome->isFinished)->toBeFalse()
            ->and($outcome->winnerUlid)->toBeNull();
    });

    test('can move a piece', function () {
        $state = CheckersBoard::createNew('p1', 'p2');

        // Move a red piece forward
        $newState = $state->withMovedPiece(5, 0, 4, 1);

        expect($newState->getPieceAt(5, 0))->toBeNull()
            ->and($newState->getPieceAt(4, 1))->not->toBeNull()
            ->and($newState->getPieceAt(4, 1)['player'])->toBe('p1');
    });

    test('piece is promoted to king when reaching opposite end', function () {
        $state = CheckersBoard::createNew('p1', 'p2');

        // Create a scenario where a red piece is near the top
        $modifiedBoard = $state->board;
        $modifiedBoard[1][0] = ['player' => 'p1', 'king' => false];
        $modifiedBoard[5][0] = null; // Remove from original position

        $state = new CheckersBoard(
            players: $state->players,
            currentPlayerUlid: $state->currentPlayerUlid,
            winnerUlid: $state->winnerUlid,
            phase: $state->phase,
            status: $state->status,
            board: $modifiedBoard,
            isDraw: false,
        );

        // Move to row 0 (top)
        $newState = $state->withMovedPiece(1, 0, 0, 1);

        expect($newState->getPieceAt(0, 1)['king'])->toBeTrue();
    });

    test('can remove a captured piece', function () {
        $state = CheckersBoard::createNew('p1', 'p2');

        // Place pieces for a capture scenario
        $modifiedBoard = $state->board;
        $modifiedBoard[4][1] = ['player' => 'p1', 'king' => false];
        $modifiedBoard[3][2] = ['player' => 'p2', 'king' => false];

        $state = new CheckersBoard(
            players: $state->players,
            currentPlayerUlid: $state->currentPlayerUlid,
            winnerUlid: $state->winnerUlid,
            phase: $state->phase,
            status: $state->status,
            board: $modifiedBoard,
            isDraw: false,
        );

        // Remove the captured piece
        $newState = $state->withRemovedPiece(3, 2);

        expect($newState->getPieceAt(3, 2))->toBeNull()
            ->and($newState->players['p2']->piecesRemaining)->toBe(11);
    });
});
