<?php

declare(strict_types=1);

use App\Enums\OutcomeType;
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
                ->and($outcome->type)->toBeNull()
                ->and($outcome->details)->toBe([]);
        });

        test('win creates finished outcome with winner', function () {
            $outcome = GameOutcome::win($this->player1Ulid, null, 'four_in_a_row');

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid)
                ->and($outcome->type)->toBe(OutcomeType::WIN)
                ->and($outcome->details['reason'])->toBe('four_in_a_row');
        });

        test('win without reason is valid', function () {
            $outcome = GameOutcome::win($this->player1Ulid);

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid)
                ->and($outcome->details)->toBe([]);
        });

        test('draw creates finished outcome without winner', function () {
            $outcome = GameOutcome::draw('board_full');

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBeNull()
                ->and($outcome->type)->toBe(OutcomeType::DRAW)
                ->and($outcome->details['reason'])->toBe('board_full');
        });

        test('draw without reason is valid', function () {
            $outcome = GameOutcome::draw();

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->type)->toBe(OutcomeType::DRAW)
                ->and($outcome->details)->toBe([]);
        });
    });

    describe('Win Conditions', function () {
        test('win outcome has correct properties', function () {
            $outcome = GameOutcome::win($this->player1Ulid, null, 'checkmate');

            expect($outcome->isFinished)->toBeTrue()
                ->and($outcome->winnerUlid)->toBe($this->player1Ulid)
                ->and($outcome->type)->toBe(OutcomeType::WIN);
        });

        test('supports various win reasons', function () {
            $reasons = ['four_in_a_row', 'checkmate', 'forfeit', 'timeout', 'resignation'];

            foreach ($reasons as $reason) {
                $outcome = GameOutcome::win($this->player1Ulid, null, $reason);
                expect($outcome->details['reason'])->toBe($reason)
                    ->and($outcome->isFinished)->toBeTrue();
            }
        });
    });

    describe('Draw Conditions', function () {
        test('draw outcome has no winner', function () {
            $outcome = GameOutcome::draw('stalemate');

            expect($outcome->winnerUlid)->toBeNull()
                ->and($outcome->type)->toBe(OutcomeType::DRAW)
                ->and($outcome->isFinished)->toBeTrue();
        });

        test('supports various draw reasons', function () {
            $reasons = ['board_full', 'stalemate', 'agreement', 'insufficient_material'];

            foreach ($reasons as $reason) {
                $outcome = GameOutcome::draw($reason);
                expect($outcome->details['reason'])->toBe($reason)
                    ->and($outcome->type)->toBe(OutcomeType::DRAW);
            }
        });
    });

    describe('In Progress State', function () {
        test('in progress has no winner or draw', function () {
            $outcome = GameOutcome::inProgress();

            expect($outcome->isFinished)->toBeFalse()
                ->and($outcome->winnerUlid)->toBeNull()
                ->and($outcome->type)->toBeNull();
        });

        test('in progress has no details', function () {
            $outcome = GameOutcome::inProgress();

            expect($outcome->details)->toBeEmpty();
        });
    });

    describe('Complex Outcomes', function () {
        test('supports rankings for multiplayer games', function () {
            $player3Ulid = (string) Str::ulid();
            $player4Ulid = (string) Str::ulid();

            $outcome = new GameOutcome(
                isFinished: true,
                winnerUlid: $this->player1Ulid,
                type: OutcomeType::WIN,
                details: [
                    'rankings' => [$this->player1Ulid, $player3Ulid, $this->player2Ulid, $player4Ulid]
                ]
            );

            expect($outcome->details['rankings'])->toHaveCount(4)
                ->and($outcome->details['rankings'][0])->toBe($this->player1Ulid)
                ->and($outcome->details['rankings'][1])->toBe($player3Ulid);
        });

        test('supports scores for scoring-based games', function () {
            $outcome = new GameOutcome(
                isFinished: true,
                winnerUlid: $this->player1Ulid,
                type: OutcomeType::WIN,
                details: [
                    'scores' => [
                        $this->player1Ulid => 26,
                        $this->player2Ulid => 78,
                    ]
                ]
            );

            expect($outcome->details['scores'])->toHaveCount(2)
                ->and($outcome->details['scores'][$this->player1Ulid])->toBe(26)
                ->and($outcome->details['scores'][$this->player2Ulid])->toBe(78);
        });

        test('supports both rankings and scores', function () {
            $outcome = new GameOutcome(
                isFinished: true,
                winnerUlid: $this->player1Ulid,
                type: OutcomeType::WIN,
                details: [
                    'rankings' => [$this->player1Ulid, $this->player2Ulid],
                    'scores' => [$this->player1Ulid => 100, $this->player2Ulid => 75],
                    'reason' => 'game_complete'
                ]
            );

            expect($outcome->details['rankings'])->toHaveCount(2)
                ->and($outcome->details['scores'])->toHaveCount(2)
                ->and($outcome->details['reason'])->toBe('game_complete');
        });
    });

    describe('Serialization', function () {
        test('toArray includes all properties for in progress', function () {
            $outcome = GameOutcome::inProgress();

            $array = $outcome->toArray();

            expect($array)->toBe([
                'is_finished' => false,
                'winner_ulid' => null,
                'winner_position' => null,
                'type' => null,
                'details' => [],
            ]);
        });

        test('toArray includes all properties for win', function () {
            $outcome = GameOutcome::win($this->player1Ulid, 1, 'four_in_a_row');

            $array = $outcome->toArray();

            expect($array)->toBe([
                'is_finished' => true,
                'winner_ulid' => $this->player1Ulid,
                'winner_position' => 1,
                'type' => 'win',
                'details' => ['reason' => 'four_in_a_row'],
            ]);
        });
    });
});
