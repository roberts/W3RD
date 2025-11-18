<?php

declare(strict_types=1);

use App\Games\GameOutcome;
use Illuminate\Support\Str;

describe('GameOutcome', function () {
    beforeEach(function () {
        $this->player1Ulid = (string) Str::ulid();
        $this->player2Ulid = (string) Str::ulid();
    });

    describe('Factory Methods', function () {
        test('inProgress creates unfinished outcome', function () {
            $outcome = GameOutcome::inProgress();

            expect($outcome->isFinished)->toBeFalse()
                ->and($outcome->winnerUlid)->toBeNull()
                ->and($outcome->isDraw)->toBeFalse()
                ->and($outcome->reason)->toBeNull()
                ->and($outcome->rankings)->toBe([])
                ->and($outcome->scores)->toBe([]);
        });

        test('win creates finished outcome with winner', function () {
            $outcome = GameOutcome::win($this->player1Ulid, 'four_in_a_row');

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid)
                ->and($outcome->isDraw)->toBeFalse()
                ->and($outcome->reason)->toBe('four_in_a_row');
        });

        test('win without reason is valid', function () {
            $outcome = GameOutcome::win($this->player1Ulid);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid)
                ->and($outcome->reason)->toBeNull();
        });

        test('draw creates finished outcome without winner', function () {
            $outcome = GameOutcome::draw('board_full');

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBeNull()
                ->and($outcome->isDraw)->toBeTrue()
                ->and($outcome->reason)->toBe('board_full');
        });

        test('draw without reason is valid', function () {
            $outcome = GameOutcome::draw();

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->isDraw)->toBeTrue()
                ->and($outcome->reason)->toBeNull();
        });
    });

    describe('Win Conditions', function () {
        test('win outcome has correct properties', function () {
            $outcome = GameOutcome::win($this->player1Ulid, 'checkmate');

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid)
                ->and($outcome->isDraw)->toBeFalse();
        });

        test('supports various win reasons', function () {
            $reasons = ['four_in_a_row', 'checkmate', 'forfeit', 'timeout', 'resignation'];

            foreach ($reasons as $reason) {
                $outcome = GameOutcome::win($this->player1Ulid, $reason);
                expect($outcome->reason)->toBe($reason)
                    ->and($outcome->isFinished)->toBeTrue();
            }
        });
    });

    describe('Draw Conditions', function () {
        test('draw outcome has no winner', function () {
            $outcome = GameOutcome::draw('stalemate');

            expect($outcome->winnerUlid)->toBeNull()
                ->and($outcome->isDraw)->toBeTrue()
                ->and($outcome->isFinished)->toBeTrue();
        });

        test('supports various draw reasons', function () {
            $reasons = ['board_full', 'stalemate', 'agreement', 'insufficient_material'];

            foreach ($reasons as $reason) {
                $outcome = GameOutcome::draw($reason);
                expect($outcome->reason)->toBe($reason)
                    ->and($outcome->isDraw)->toBeTrue();
            }
        });
    });

    describe('In Progress State', function () {
        test('in progress has no winner or draw', function () {
            $outcome = GameOutcome::inProgress();

            expect($outcome->isFinished)->toBeFalse()
                ->and($outcome->winnerUlid)->toBeNull()
                ->and($outcome->isDraw)->toBeFalse();
        });

        test('in progress has no rankings or scores', function () {
            $outcome = GameOutcome::inProgress();

            expect($outcome->rankings)->toBeEmpty()
                ->and($outcome->scores)->toBeEmpty();
        });
    });

    describe('Complex Outcomes', function () {
        test('supports rankings for multiplayer games', function () {
            $player3Ulid = (string) Str::ulid();
            $player4Ulid = (string) Str::ulid();

            $outcome = new GameOutcome(
                isFinished: true,
                winnerUlid: $this->player1Ulid,
                rankings: [$this->player1Ulid, $player3Ulid, $this->player2Ulid, $player4Ulid]
            );

            expect($outcome->rankings)->toHaveCount(4)
                ->and($outcome->rankings[0])->toBe($this->player1Ulid)
                ->and($outcome->rankings[1])->toBe($player3Ulid);
        });

        test('supports scores for scoring-based games', function () {
            $outcome = new GameOutcome(
                isFinished: true,
                winnerUlid: $this->player1Ulid,
                scores: [
                    $this->player1Ulid => 26,
                    $this->player2Ulid => 78,
                ]
            );

            expect($outcome->scores)->toHaveCount(2)
                ->and($outcome->scores[$this->player1Ulid])->toBe(26)
                ->and($outcome->scores[$this->player2Ulid])->toBe(78);
        });

        test('supports both rankings and scores', function () {
            $outcome = new GameOutcome(
                isFinished: true,
                winnerUlid: $this->player1Ulid,
                rankings: [$this->player1Ulid, $this->player2Ulid],
                scores: [$this->player1Ulid => 100, $this->player2Ulid => 75],
                reason: 'game_complete'
            );

            expect($outcome->rankings)->toHaveCount(2)
                ->and($outcome->scores)->toHaveCount(2)
                ->and($outcome->reason)->toBe('game_complete');
        });
    });

    describe('Serialization', function () {
        test('toArray includes all properties for in progress', function () {
            $outcome = GameOutcome::inProgress();

            $array = $outcome->toArray();

            expect($array)->toBe([
                'is_finished' => false,
                'winner_ulid' => null,
                'is_draw' => false,
                'rankings' => [],
                'scores' => [],
                'reason' => null,
            ]);
        });

        test('toArray includes all properties for win', function () {
            $outcome = GameOutcome::win($this->player1Ulid, 'four_in_a_row');

            $array = $outcome->toArray();

            expect($array)->toBe([
                'is_finished' => true,
                'winner_ulid' => $this->player1Ulid,
                'is_draw' => false,
                'rankings' => [],
                'scores' => [],
                'reason' => 'four_in_a_row',
            ]);
        });

        test('toArray includes all properties for draw', function () {
            $outcome = GameOutcome::draw('board_full');

            $array = $outcome->toArray();

            expect($array)->toBe([
                'is_finished' => true,
                'winner_ulid' => null,
                'is_draw' => true,
                'rankings' => [],
                'scores' => [],
                'reason' => 'board_full',
            ]);
        });

        test('toArray includes rankings and scores when present', function () {
            $outcome = new GameOutcome(
                isFinished: true,
                winnerUlid: $this->player1Ulid,
                rankings: [$this->player1Ulid, $this->player2Ulid],
                scores: [$this->player1Ulid => 100, $this->player2Ulid => 50]
            );

            $array = $outcome->toArray();

            expect($array['rankings'])->toHaveCount(2)
                ->and($array['scores'])->toHaveCount(2)
                ->and($array['winner_ulid'])->toBe($this->player1Ulid);
        });
    });

    describe('Readonly Properties', function () {
        test('all properties are readonly', function () {
            $outcome = GameOutcome::inProgress();

            expect($outcome)->toHaveProperty('isFinished')
                ->and($outcome)->toHaveProperty('winnerUlid')
                ->and($outcome)->toHaveProperty('isDraw')
                ->and($outcome)->toHaveProperty('rankings')
                ->and($outcome)->toHaveProperty('scores')
                ->and($outcome)->toHaveProperty('reason');
        });
    });

    describe('Edge Cases', function () {
        test('handles empty strings for reason', function () {
            $outcome = GameOutcome::win($this->player1Ulid, '');

            expect($outcome->reason)->toBe('');
        });

        test('handles float scores', function () {
            $outcome = new GameOutcome(
                isFinished: true,
                scores: [$this->player1Ulid => 95.5, $this->player2Ulid => 87.3]
            );

            expect($outcome->scores[$this->player1Ulid])->toBe(95.5)
                ->and($outcome->scores[$this->player2Ulid])->toBe(87.3);
        });

        test('rankings can be empty even when finished', function () {
            $outcome = GameOutcome::win($this->player1Ulid);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->rankings)->toBeEmpty();
        });
    });
});
