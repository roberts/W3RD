<?php

declare(strict_types=1);

use App\Enums\GamePhase;
use App\Enums\GameStatus;
use App\GameTitles\Checkers\CheckersBoard;
use Illuminate\Support\Str;

describe('Checkers Board', function () {
    beforeEach(function () {
        $this->player1Ulid = (string) Str::ulid();
        $this->player2Ulid = (string) Str::ulid();
    });

    describe('Factory Methods', function () {
        test('createNew initializes valid game state', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            expect($state->currentPlayerUlid)->toBe($this->player1Ulid)
                ->and($state->winnerUlid)->toBeNull()
                ->and($state->phase)->toBe(GamePhase::ACTIVE)
                ->and($state->status)->toBe(GameStatus::ACTIVE)
                ->and($state->isDraw)->toBeFalse()
                ->and($state->players)->toHaveCount(2);
        });

        test('createNew initializes 8x8 board', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            expect($state->board)->toHaveCount(8);
            foreach ($state->board as $row) {
                expect($row)->toHaveCount(8);
            }
        });

        test('createNew places 12 red pieces for player one', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $redPieces = 0;
            for ($row = 5; $row <= 7; $row++) {
                for ($col = 0; $col < 8; $col++) {
                    $piece = $state->getPieceAt($row, $col);
                    if ($piece && $piece['player'] === $this->player1Ulid) {
                        $redPieces++;
                        expect($piece['king'])->toBeFalse();
                    }
                }
            }

            expect($redPieces)->toBe(12)
                ->and($state->players[$this->player1Ulid]->piecesRemaining)->toBe(12);
        });

        test('createNew places 12 black pieces for player two', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $blackPieces = 0;
            for ($row = 0; $row <= 2; $row++) {
                for ($col = 0; $col < 8; $col++) {
                    $piece = $state->getPieceAt($row, $col);
                    if ($piece && $piece['player'] === $this->player2Ulid) {
                        $blackPieces++;
                        expect($piece['king'])->toBeFalse();
                    }
                }
            }

            expect($blackPieces)->toBe(12)
                ->and($state->players[$this->player2Ulid]->piecesRemaining)->toBe(12);
        });

        test('createNew places pieces only on dark squares', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            // Check all starting positions
            for ($row = 0; $row <= 2; $row++) {
                for ($col = 0; $col < 8; $col++) {
                    if (($row + $col) % 2 === 1) {
                        // Dark square - should have a piece
                        expect($state->getPieceAt($row, $col))->not->toBeNull();
                    } else {
                        // Light square - should be empty
                        expect($state->getPieceAt($row, $col))->toBeNull();
                    }
                }
            }

            for ($row = 5; $row <= 7; $row++) {
                for ($col = 0; $col < 8; $col++) {
                    if (($row + $col) % 2 === 1) {
                        expect($state->getPieceAt($row, $col))->not->toBeNull();
                    } else {
                        expect($state->getPieceAt($row, $col))->toBeNull();
                    }
                }
            }
        });

        test('createNew assigns correct player colors', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            expect($state->players[$this->player1Ulid]->color)->toBe('red')
                ->and($state->players[$this->player2Ulid]->color)->toBe('black');
        });
    });

    describe('Serialization', function () {
        test('toArray converts state to array', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $array = $state->toArray();

            expect($array)->toBeArray()
                ->and($array)->toHaveKeys([
                    'players',
                    'currentPlayerUlid',
                    'winnerUlid',
                    'phase',
                    'status',
                    'board',
                    'isDraw',
                ]);
        });

        test('fromArray reconstructs state from array', function () {
            $original = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);
            $array = $original->toArray();

            $restored = CheckersBoard::fromArray($array);

            expect($restored->currentPlayerUlid)->toBe($original->currentPlayerUlid)
                ->and($restored->board)->toBe($original->board)
                ->and($restored->isDraw)->toBe($original->isDraw);
        });

        test('fromArray handles missing fields with defaults', function () {
            $minimal = [
                'players' => [],
                'board' => [],
            ];

            $state = CheckersBoard::fromArray($minimal);

            expect($state->isDraw)->toBeFalse()
                ->and($state->phase)->toBe(GamePhase::ACTIVE)
                ->and($state->status)->toBe(GameStatus::ACTIVE);
        });

        test('serialization roundtrip preserves piece data', function () {
            $original = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $restored = CheckersBoard::fromArray($original->toArray());

            // Check a few pieces to ensure they were preserved
            expect($restored->getPieceAt(0, 1))->toBe($original->getPieceAt(0, 1))
                ->and($restored->getPieceAt(5, 0))->toBe($original->getPieceAt(5, 0));
        });
    });

    describe('Immutability', function () {
        test('withMovedPiece returns new instance', function () {
            $original = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $original->withMovedPiece(5, 0, 4, 1);

            expect($modified)->not->toBe($original)
                ->and($original->getPieceAt(5, 0))->not->toBeNull()
                ->and($original->getPieceAt(4, 1))->toBeNull()
                ->and($modified->getPieceAt(5, 0))->toBeNull()
                ->and($modified->getPieceAt(4, 1))->not->toBeNull();
        });

        test('withRemovedPiece returns new instance', function () {
            $original = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $original->withRemovedPiece(0, 1);

            expect($modified)->not->toBe($original)
                ->and($original->getPieceAt(0, 1))->not->toBeNull()
                ->and($modified->getPieceAt(0, 1))->toBeNull();
        });

        test('withWinner returns new instance', function () {
            $original = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $original->withWinner($this->player1Ulid);

            expect($modified)->not->toBe($original)
                ->and($original->winnerUlid)->toBeNull()
                ->and($modified->winnerUlid)->toBe($this->player1Ulid)
                ->and($modified->status)->toBe(GameStatus::COMPLETED)
                ->and($modified->phase)->toBe(GamePhase::COMPLETED);
        });

        test('withDraw returns new instance', function () {
            $original = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $original->withDraw();

            expect($modified)->not->toBe($original)
                ->and($original->isDraw)->toBeFalse()
                ->and($modified->isDraw)->toBeTrue()
                ->and($modified->status)->toBe(GameStatus::COMPLETED)
                ->and($modified->phase)->toBe(GamePhase::COMPLETED);
        });

        test('withNextPlayer returns new instance', function () {
            $original = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $original->withNextPlayer();

            expect($modified)->not->toBe($original)
                ->and($original->currentPlayerUlid)->toBe($this->player1Ulid)
                ->and($modified->currentPlayerUlid)->toBe($this->player2Ulid);
        });
    });

    describe('Board Query Methods', function () {
        test('getPieceAt returns correct piece', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $piece = $state->getPieceAt(0, 1);

            expect($piece)->not->toBeNull()
                ->and($piece['player'])->toBe($this->player2Ulid)
                ->and($piece['king'])->toBeFalse();
        });

        test('getPieceAt returns null for empty square', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            expect($state->getPieceAt(3, 3))->toBeNull();
        });

        test('getPieceAt returns null for out of bounds', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            expect($state->getPieceAt(-1, 3))->toBeNull()
                ->and($state->getPieceAt(8, 3))->toBeNull()
                ->and($state->getPieceAt(3, -1))->toBeNull()
                ->and($state->getPieceAt(3, 8))->toBeNull();
        });
    });

    describe('Piece Movement', function () {
        test('withMovedPiece updates piece position', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $state->withMovedPiece(5, 0, 4, 1);

            expect($modified->getPieceAt(5, 0))->toBeNull()
                ->and($modified->getPieceAt(4, 1))->not->toBeNull()
                ->and($modified->getPieceAt(4, 1)['player'])->toBe($this->player1Ulid);
        });

        test('withMovedPiece promotes red piece to king at row 0', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            // Place red piece at row 1
            $state = $state->withMovedPiece(5, 0, 1, 4);

            // Move to row 0 (promotion row for red)
            $modified = $state->withMovedPiece(1, 4, 0, 5);

            $piece = $modified->getPieceAt(0, 5);
            expect($piece)->not->toBeNull()
                ->and($piece['king'])->toBeTrue();
        });

        test('withMovedPiece promotes black piece to king at row 7', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            // Place black piece at row 6
            $state = $state->withMovedPiece(0, 1, 6, 5);

            // Move to row 7 (promotion row for black)
            $modified = $state->withMovedPiece(6, 5, 7, 6);

            $piece = $modified->getPieceAt(7, 6);
            expect($piece)->not->toBeNull()
                ->and($piece['king'])->toBeTrue();
        });

        test('withRemovedPiece clears piece and updates count', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $state->withRemovedPiece(0, 1);

            expect($modified->getPieceAt(0, 1))->toBeNull()
                ->and($modified->players[$this->player2Ulid]->piecesRemaining)->toBe(11);
        });

        test('multiple piece removals update count correctly', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $state
                ->withRemovedPiece(0, 1)
                ->withRemovedPiece(0, 3)
                ->withRemovedPiece(0, 5);

            expect($modified->players[$this->player2Ulid]->piecesRemaining)->toBe(9);
        });
    });

    describe('Player Turn Management', function () {
        test('withNextPlayer alternates between players', function () {
            $state = CheckersBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $turn1 = $state->withNextPlayer();
            $turn2 = $turn1->withNextPlayer();

            expect($state->currentPlayerUlid)->toBe($this->player1Ulid)
                ->and($turn1->currentPlayerUlid)->toBe($this->player2Ulid)
                ->and($turn2->currentPlayerUlid)->toBe($this->player1Ulid);
        });
    });
});
