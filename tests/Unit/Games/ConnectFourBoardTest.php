<?php

declare(strict_types=1);

use App\Enums\GamePhase;
use App\Enums\GameStatus;
use App\GameTitles\ConnectFour\ConnectFourBoard;
use Illuminate\Support\Str;

describe('ConnectFour ConnectFourBoard', function () {
    beforeEach(function () {
        $this->player1Ulid = (string) Str::ulid();
        $this->player2Ulid = (string) Str::ulid();
    });

    describe('Factory Methods', function () {
        test('createNew initializes valid game state', function () {
            $state = ConnectFourBoard::createNew(
                $this->player1Ulid,
                $this->player2Ulid,
                columns: 7,
                rows: 6,
                connectCount: 4
            );

            expect($state->columns)->toBe(7)
                ->and($state->rows)->toBe(6)
                ->and($state->connectCount)->toBe(4)
                ->and($state->currentPlayerUlid)->toBe($this->player1Ulid)
                ->and($state->winnerUlid)->toBeNull()
                ->and($state->phase)->toBe(GamePhase::ACTIVE)
                ->and($state->status)->toBe(GameStatus::ACTIVE)
                ->and($state->isDraw)->toBeFalse()
                ->and($state->players)->toHaveCount(2);
        });

        test('createNew initializes empty board', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            expect($state->board)->toHaveCount(6);
            foreach ($state->board as $row) {
                expect($row)->toHaveCount(7);
                foreach ($row as $cell) {
                    expect($cell)->toBeNull();
                }
            }
        });

        test('createNew supports custom board dimensions', function () {
            $state = ConnectFourBoard::createNew(
                $this->player1Ulid,
                $this->player2Ulid,
                columns: 8,
                rows: 7,
                connectCount: 5
            );

            expect($state->columns)->toBe(8)
                ->and($state->rows)->toBe(7)
                ->and($state->connectCount)->toBe(5)
                ->and($state->board)->toHaveCount(7)
                ->and($state->board[0])->toHaveCount(8);
        });

        test('createNew assigns correct player colors', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            expect($state->players[$this->player1Ulid]->color)->toBe('red')
                ->and($state->players[$this->player2Ulid]->color)->toBe('yellow');
        });

        test('createNew assigns correct player positions', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            expect($state->players[$this->player1Ulid]->position)->toBe(1)
                ->and($state->players[$this->player2Ulid]->position)->toBe(2);
        });
    });

    describe('Serialization', function () {
        test('toArray converts state to array', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $array = $state->toArray();

            expect($array)->toBeArray()
                ->and($array)->toHaveKeys([
                    'players',
                    'current_player_ulid',
                    'winner_ulid',
                    'phase',
                    'status',
                    'board',
                    'columns',
                    'rows',
                    'connect_count',
                    'is_draw',
                ]);
        });

        test('fromArray reconstructs state from array', function () {
            $original = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);
            $array = $original->toArray();

            $restored = ConnectFourBoard::fromArray($array);

            expect($restored->columns)->toBe($original->columns)
                ->and($restored->rows)->toBe($original->rows)
                ->and($restored->connectCount)->toBe($original->connectCount)
                ->and($restored->currentPlayerUlid)->toBe($original->currentPlayerUlid)
                ->and($restored->board)->toBe($original->board)
                ->and($restored->isDraw)->toBe($original->isDraw);
        });

        test('fromArray handles missing optional fields with defaults', function () {
            $minimal = [
                'players' => [],
                'board' => [],
            ];

            $state = ConnectFourBoard::fromArray($minimal);

            expect($state->columns)->toBe(7)
                ->and($state->rows)->toBe(6)
                ->and($state->connectCount)->toBe(4)
                ->and($state->isDraw)->toBeFalse()
                ->and($state->phase)->toBe(GamePhase::ACTIVE)
                ->and($state->status)->toBe(GameStatus::ACTIVE);
        });

        test('serialization roundtrip preserves all data', function () {
            $original = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 3, $this->player1Ulid)
                ->withPieceAt(5, 4, $this->player2Ulid);

            $restored = ConnectFourBoard::fromArray($original->toArray());

            expect($restored->board)->toBe($original->board)
                ->and($restored->currentPlayerUlid)->toBe($original->currentPlayerUlid)
                ->and($restored->players)->toHaveCount(count($original->players));
        });
    });

    describe('Immutability', function () {
        test('withPieceAt returns new instance', function () {
            $original = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $original->withPieceAt(5, 3, $this->player1Ulid);

            expect($modified)->not->toBe($original)
                ->and($original->getPieceAt(5, 3))->toBeNull()
                ->and($modified->getPieceAt(5, 3))->toBe($this->player1Ulid);
        });

        test('withNextPlayer returns new instance', function () {
            $original = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $original->withNextPlayer();

            expect($modified)->not->toBe($original)
                ->and($original->currentPlayerUlid)->toBe($this->player1Ulid)
                ->and($modified->currentPlayerUlid)->toBe($this->player2Ulid);
        });

        test('withWinner returns new instance', function () {
            $original = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $original->withWinner($this->player1Ulid);

            expect($modified)->not->toBe($original)
                ->and($original->winnerUlid)->toBeNull()
                ->and($modified->winnerUlid)->toBe($this->player1Ulid)
                ->and($modified->status)->toBe(GameStatus::COMPLETED)
                ->and($modified->phase)->toBe(GamePhase::COMPLETED);
        });

        test('withDraw returns new instance', function () {
            $original = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $original->withDraw();

            expect($modified)->not->toBe($original)
                ->and($original->isDraw)->toBeFalse()
                ->and($modified->isDraw)->toBeTrue()
                ->and($modified->status)->toBe(GameStatus::COMPLETED)
                ->and($modified->phase)->toBe(GamePhase::COMPLETED);
        });

        test('withBoard returns new instance', function () {
            $original = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);
            $newBoard = array_fill(0, 6, array_fill(0, 7, $this->player1Ulid));

            $modified = $original->withBoard($newBoard);

            expect($modified)->not->toBe($original)
                ->and($original->board[0][0])->toBeNull()
                ->and($modified->board[0][0])->toBe($this->player1Ulid);
        });

        test('multiple withX calls chain correctly', function () {
            $original = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $modified = $original
                ->withPieceAt(5, 3, $this->player1Ulid)
                ->withNextPlayer()
                ->withPieceAt(5, 4, $this->player2Ulid)
                ->withNextPlayer();

            expect($modified)->not->toBe($original)
                ->and($modified->getPieceAt(5, 3))->toBe($this->player1Ulid)
                ->and($modified->getPieceAt(5, 4))->toBe($this->player2Ulid)
                ->and($modified->currentPlayerUlid)->toBe($this->player1Ulid);
        });
    });

    describe('Board Query Methods', function () {
        test('getPieceAt returns correct piece', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 3, $this->player1Ulid);

            expect($state->getPieceAt(5, 3))->toBe($this->player1Ulid)
                ->and($state->getPieceAt(5, 4))->toBeNull();
        });

        test('getPieceAt returns null for out of bounds', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            expect($state->getPieceAt(-1, 3))->toBeNull()
                ->and($state->getPieceAt(6, 3))->toBeNull()
                ->and($state->getPieceAt(3, -1))->toBeNull()
                ->and($state->getPieceAt(3, 7))->toBeNull();
        });

        test('getLowestEmptyRow returns bottom row for empty column', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            expect($state->getLowestEmptyRow(3))->toBe(5);
        });

        test('getLowestEmptyRow returns correct row as column fills', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 3, $this->player1Ulid)
                ->withPieceAt(4, 3, $this->player2Ulid)
                ->withPieceAt(3, 3, $this->player1Ulid);

            expect($state->getLowestEmptyRow(3))->toBe(2);
        });

        test('getLowestEmptyRow returns null for full column', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);
            for ($row = 0; $row < 6; $row++) {
                $state = $state->withPieceAt($row, 3, $this->player1Ulid);
            }

            expect($state->getLowestEmptyRow(3))->toBeNull();
        });

        test('getLowestEmptyRow returns null for invalid column', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            expect($state->getLowestEmptyRow(-1))->toBeNull()
                ->and($state->getLowestEmptyRow(7))->toBeNull();
        });

        test('isBoardFull returns false for empty board', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            expect($state->isBoardFull())->toBeFalse();
        });

        test('isBoardFull returns false for partially filled board', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid)
                ->withPieceAt(5, 3, $this->player1Ulid)
                ->withPieceAt(4, 3, $this->player2Ulid);

            expect($state->isBoardFull())->toBeFalse();
        });

        test('isBoardFull returns true when top row is filled', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            // Fill top row (row 0)
            for ($col = 0; $col < 7; $col++) {
                $state = $state->withPieceAt(0, $col, $this->player1Ulid);
            }

            expect($state->isBoardFull())->toBeTrue();
        });

        test('isBoardFull returns true when entire board is filled', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            for ($row = 0; $row < 6; $row++) {
                for ($col = 0; $col < 7; $col++) {
                    $state = $state->withPieceAt($row, $col, $this->player1Ulid);
                }
            }

            expect($state->isBoardFull())->toBeTrue();
        });
    });

    describe('Player Turn Management', function () {
        test('withNextPlayer alternates between players', function () {
            $state = ConnectFourBoard::createNew($this->player1Ulid, $this->player2Ulid);

            $turn1 = $state->withNextPlayer();
            $turn2 = $turn1->withNextPlayer();
            $turn3 = $turn2->withNextPlayer();

            expect($state->currentPlayerUlid)->toBe($this->player1Ulid)
                ->and($turn1->currentPlayerUlid)->toBe($this->player2Ulid)
                ->and($turn2->currentPlayerUlid)->toBe($this->player1Ulid)
                ->and($turn3->currentPlayerUlid)->toBe($this->player2Ulid);
        });
    });
});
