<?php

declare(strict_types=1);

use App\Games\ConnectFour\ConnectFourArbiter;
use App\Games\ConnectFour\ConnectFourBoard;
use App\Models\Game\Game;
use Illuminate\Support\Str;

describe('ConnectFour Win Conditions', function () {
    beforeEach(function () {
        $this->player1Ulid = (string) Str::ulid();
        $this->player2Ulid = (string) Str::ulid();
        $this->game = new Game(['game_state' => []]);
        $this->arbiter = new ConnectFourArbiter();
    });

    describe('Horizontal Wins', function () {
        test('detects horizontal win in bottom row', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 0, $this->player1Ulid)
                ->withPieceAt(5, 1, $this->player1Ulid)
                ->withPieceAt(5, 2, $this->player1Ulid)
                ->withPieceAt(5, 3, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid)
                ->and($outcome->details['reason'])->toBe('four_in_a_row');
        });

        test('detects horizontal win in top row', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(0, 2, $this->player2Ulid)
                ->withPieceAt(0, 3, $this->player2Ulid)
                ->withPieceAt(0, 4, $this->player2Ulid)
                ->withPieceAt(0, 5, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player2Ulid);
        });

        test('detects horizontal win in middle row', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(3, 1, $this->player1Ulid)
                ->withPieceAt(3, 2, $this->player1Ulid)
                ->withPieceAt(3, 3, $this->player1Ulid)
                ->withPieceAt(3, 4, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid);
        });

        test('detects horizontal win at right edge', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(4, 3, $this->player2Ulid)
                ->withPieceAt(4, 4, $this->player2Ulid)
                ->withPieceAt(4, 5, $this->player2Ulid)
                ->withPieceAt(4, 6, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player2Ulid);
        });

        test('does not detect horizontal win with only 3 pieces', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 0, $this->player1Ulid)
                ->withPieceAt(5, 1, $this->player1Ulid)
                ->withPieceAt(5, 2, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeFalse()
                ->and($outcome->winnerUlid)->toBeNull();
        });

        test('does not detect horizontal win with broken sequence', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 0, $this->player1Ulid)
                ->withPieceAt(5, 1, $this->player1Ulid)
                ->withPieceAt(5, 2, $this->player2Ulid)
                ->withPieceAt(5, 3, $this->player1Ulid)
                ->withPieceAt(5, 4, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeFalse();
        });
    });

    describe('Vertical Wins', function () {
        test('detects vertical win in leftmost column', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 0, $this->player1Ulid)
                ->withPieceAt(4, 0, $this->player1Ulid)
                ->withPieceAt(3, 0, $this->player1Ulid)
                ->withPieceAt(2, 0, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid);
        });

        test('detects vertical win in rightmost column', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 6, $this->player2Ulid)
                ->withPieceAt(4, 6, $this->player2Ulid)
                ->withPieceAt(3, 6, $this->player2Ulid)
                ->withPieceAt(2, 6, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player2Ulid);
        });

        test('detects vertical win in middle column', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 3, $this->player1Ulid)
                ->withPieceAt(4, 3, $this->player1Ulid)
                ->withPieceAt(3, 3, $this->player1Ulid)
                ->withPieceAt(2, 3, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid);
        });

        test('detects vertical win at top of column', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(3, 2, $this->player2Ulid)
                ->withPieceAt(2, 2, $this->player2Ulid)
                ->withPieceAt(1, 2, $this->player2Ulid)
                ->withPieceAt(0, 2, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player2Ulid);
        });

        test('does not detect vertical win with only 3 pieces', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 3, $this->player1Ulid)
                ->withPieceAt(4, 3, $this->player1Ulid)
                ->withPieceAt(3, 3, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeFalse();
        });
    });

    describe('Diagonal Wins - Down-Right', function () {
        test('detects diagonal win from bottom-left', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 0, $this->player1Ulid)
                ->withPieceAt(4, 1, $this->player1Ulid)
                ->withPieceAt(3, 2, $this->player1Ulid)
                ->withPieceAt(2, 3, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid);
        });

        test('detects diagonal win in center', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(3, 1, $this->player2Ulid)
                ->withPieceAt(2, 2, $this->player2Ulid)
                ->withPieceAt(1, 3, $this->player2Ulid)
                ->withPieceAt(0, 4, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player2Ulid);
        });

        test('detects diagonal win ending at top-right corner', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(3, 3, $this->player1Ulid)
                ->withPieceAt(2, 4, $this->player1Ulid)
                ->withPieceAt(1, 5, $this->player1Ulid)
                ->withPieceAt(0, 6, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid);
        });

        test('does not detect diagonal win with broken sequence', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 0, $this->player1Ulid)
                ->withPieceAt(4, 1, $this->player1Ulid)
                ->withPieceAt(3, 2, $this->player2Ulid)
                ->withPieceAt(2, 3, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeFalse();
        });
    });

    describe('Diagonal Wins - Down-Left', function () {
        test('detects diagonal win from bottom-right', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 6, $this->player2Ulid)
                ->withPieceAt(4, 5, $this->player2Ulid)
                ->withPieceAt(3, 4, $this->player2Ulid)
                ->withPieceAt(2, 3, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player2Ulid);
        });

        test('detects diagonal win in center going left', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(3, 5, $this->player1Ulid)
                ->withPieceAt(2, 4, $this->player1Ulid)
                ->withPieceAt(1, 3, $this->player1Ulid)
                ->withPieceAt(0, 2, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid);
        });

        test('detects diagonal win ending at top-left', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(3, 3, $this->player2Ulid)
                ->withPieceAt(2, 2, $this->player2Ulid)
                ->withPieceAt(1, 1, $this->player2Ulid)
                ->withPieceAt(0, 0, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player2Ulid);
        });
    });

    describe('Edge Cases', function () {
        test('detects win at board corners', function () {
            // Top-left corner horizontal
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(0, 0, $this->player1Ulid)
                ->withPieceAt(0, 1, $this->player1Ulid)
                ->withPieceAt(0, 2, $this->player1Ulid)
                ->withPieceAt(0, 3, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid);
        });

        test('detects win with more than 4 in a row', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 0, $this->player1Ulid)
                ->withPieceAt(5, 1, $this->player1Ulid)
                ->withPieceAt(5, 2, $this->player1Ulid)
                ->withPieceAt(5, 3, $this->player1Ulid)
                ->withPieceAt(5, 4, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid);
        });

        test('does not detect win with alternating pieces', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 0, $this->player1Ulid)
                ->withPieceAt(5, 1, $this->player2Ulid)
                ->withPieceAt(5, 2, $this->player1Ulid)
                ->withPieceAt(5, 3, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeFalse();
        });

        test('detects win in complex board state', function () {
            // Create a more controlled complex state with no win initially
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 0, $this->player1Ulid)
                ->withPieceAt(5, 1, $this->player2Ulid)
                ->withPieceAt(4, 1, $this->player1Ulid)
                ->withPieceAt(5, 2, $this->player2Ulid)
                ->withPieceAt(4, 2, $this->player1Ulid)
                ->withPieceAt(3, 2, $this->player1Ulid)
                ->withPieceAt(5, 3, $this->player2Ulid);

            // No winner yet
            $outcome = $this->arbiter->checkWinCondition($state);
            expect($outcome->isFinished)->toBeFalse();

            // Add winning piece for player2 (vertical in column 3)
            $state = $state
                ->withPieceAt(4, 3, $this->player2Ulid)
                ->withPieceAt(3, 3, $this->player2Ulid)
                ->withPieceAt(2, 3, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player2Ulid);
        });
    });

    describe('Draw Condition', function () {
        test('prioritizes win detection over draw when board is full', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            // Fill most of the board in checkerboard pattern
            for ($row = 0; $row < 6; $row++) {
                for ($col = 0; $col < 7; $col++) {
                    $player = (($row + $col) % 2 === 0) ? $this->player1Ulid : $this->player2Ulid;
                    $state = $state->withPieceAt($row, $col, $player);
                }
            }

            $outcome = $this->arbiter->checkWinCondition($state);

            // Checkerboard creates diagonal wins, so winner should be detected
            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->not->toBeNull();
        });

        test('does not detect draw on empty board', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeFalse()
                ->and($outcome->winnerUlid)->toBeNull();
        });

        test('does not detect draw on partially filled board', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 0, $this->player1Ulid)
                ->withPieceAt(5, 1, $this->player2Ulid)
                ->withPieceAt(5, 2, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeFalse();
        });
    });

    describe('Custom Board Sizes', function () {
        test('detects win on 8x7 board', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid, columns: 8, rows: 7)
                ->withPieceAt(6, 4, $this->player1Ulid)
                ->withPieceAt(6, 5, $this->player1Ulid)
                ->withPieceAt(6, 6, $this->player1Ulid)
                ->withPieceAt(6, 7, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid);
        });

        test('detects vertical win on taller board', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid, columns: 7, rows: 8)
                ->withPieceAt(7, 3, $this->player2Ulid)
                ->withPieceAt(6, 3, $this->player2Ulid)
                ->withPieceAt(5, 3, $this->player2Ulid)
                ->withPieceAt(4, 3, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player2Ulid);
        });
    });

    describe('No Winner Scenarios', function () {
        test('returns in progress for empty board', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeFalse()
                ->and($outcome->winnerUlid)->toBeNull();
        });

        test('returns in progress for single piece', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 3, $this->player1Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeFalse();
        });

        test('returns in progress with scattered pieces', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 0, $this->player1Ulid)
                ->withPieceAt(5, 3, $this->player2Ulid)
                ->withPieceAt(4, 1, $this->player1Ulid)
                ->withPieceAt(3, 5, $this->player2Ulid);

            $outcome = $this->arbiter->checkWinCondition($state);

            expect($outcome->isFinished)->toBeFalse();
        });
    });
});
