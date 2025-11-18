<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Enums\GameTitle;
use App\Enums\LobbyStatus;
use App\Enums\ActionType;

describe('GameTitle Enum', function () {
    describe('Values', function () {
        test('has all expected game titles', function () {
            expect(GameTitle::cases())->toHaveCount(4)
                ->and(GameTitle::VALIDATE_FOUR->value)->toBe('validate-four')
                ->and(GameTitle::CHECKERS->value)->toBe('checkers')
                ->and(GameTitle::HEARTS->value)->toBe('hearts')
                ->and(GameTitle::SPADES->value)->toBe('spades');
        });
    });

    describe('Labels', function () {
        test('returns correct labels', function () {
            expect(GameTitle::VALIDATE_FOUR->label())->toBe('Validate Four')
                ->and(GameTitle::CHECKERS->label())->toBe('Checkers')
                ->and(GameTitle::HEARTS->label())->toBe('Hearts')
                ->and(GameTitle::SPADES->label())->toBe('Spades');
        });
    });

    describe('Player Counts', function () {
        test('returns correct min players', function () {
            expect(GameTitle::VALIDATE_FOUR->minPlayers())->toBe(2)
                ->and(GameTitle::CHECKERS->minPlayers())->toBe(2)
                ->and(GameTitle::HEARTS->minPlayers())->toBe(4)
                ->and(GameTitle::SPADES->minPlayers())->toBe(4);
        });

        test('returns correct max players', function () {
            expect(GameTitle::VALIDATE_FOUR->maxPlayers())->toBe(2)
                ->and(GameTitle::CHECKERS->maxPlayers())->toBe(2)
                ->and(GameTitle::HEARTS->maxPlayers())->toBe(4)
                ->and(GameTitle::SPADES->maxPlayers())->toBe(4);
        });

        test('all current games require exact player count', function () {
            foreach (GameTitle::cases() as $title) {
                expect($title->requiresExactPlayerCount())->toBeTrue();
            }
        });

        test('min and max players match for exact count games', function () {
            foreach (GameTitle::cases() as $title) {
                if ($title->requiresExactPlayerCount()) {
                    expect($title->minPlayers())->toBe($title->maxPlayers());
                }
            }
        });
    });

    describe('Slug Methods', function () {
        test('slug returns value', function () {
            expect(GameTitle::VALIDATE_FOUR->slug())->toBe('validate-four')
                ->and(GameTitle::CHECKERS->slug())->toBe('checkers');
        });

        test('fromSlug returns correct enum', function () {
            expect(GameTitle::fromSlug('validate-four'))->toBe(GameTitle::VALIDATE_FOUR)
                ->and(GameTitle::fromSlug('checkers'))->toBe(GameTitle::CHECKERS)
                ->and(GameTitle::fromSlug('hearts'))->toBe(GameTitle::HEARTS);
        });

        test('fromSlug returns null for invalid slug', function () {
            expect(GameTitle::fromSlug('invalid-game'))->toBeNull()
                ->and(GameTitle::fromSlug(''))->toBeNull();
        });
    });
});

describe('GameStatus Enum', function () {
    describe('Values', function () {
        test('has all expected statuses', function () {
            expect(GameStatus::cases())->toHaveCount(5)
                ->and(GameStatus::PENDING->value)->toBe('pending')
                ->and(GameStatus::ACTIVE->value)->toBe('active')
                ->and(GameStatus::PAUSED->value)->toBe('paused')
                ->and(GameStatus::COMPLETED->value)->toBe('completed')
                ->and(GameStatus::ABANDONED->value)->toBe('abandoned');
        });
    });

    describe('Labels', function () {
        test('returns correct labels', function () {
            expect(GameStatus::PENDING->label())->toBe('Pending')
                ->and(GameStatus::ACTIVE->label())->toBe('Active')
                ->and(GameStatus::PAUSED->label())->toBe('Paused')
                ->and(GameStatus::COMPLETED->label())->toBe('Completed')
                ->and(GameStatus::ABANDONED->label())->toBe('Abandoned');
        });
    });

    describe('Descriptions', function () {
        test('returns descriptive text for each status', function () {
            foreach (GameStatus::cases() as $status) {
                expect($status->description())
                    ->toBeString()
                    ->not->toBeEmpty();
            }
        });

        test('descriptions contain status context', function () {
            expect(GameStatus::PENDING->description())->toContain('Waiting')
                ->and(GameStatus::ACTIVE->description())->toContain('progress')
                ->and(GameStatus::COMPLETED->description())->toContain('finished');
        });
    });

    describe('State Checking', function () {
        test('isPlayable returns true for active and paused states', function () {
            expect(GameStatus::ACTIVE->isPlayable())->toBeTrue()
                ->and(GameStatus::PAUSED->isPlayable())->toBeTrue();
        });

        test('isPlayable returns false for non-playable states', function () {
            expect(GameStatus::PENDING->isPlayable())->toBeFalse()
                ->and(GameStatus::COMPLETED->isPlayable())->toBeFalse()
                ->and(GameStatus::ABANDONED->isPlayable())->toBeFalse();
        });

        test('isFinished returns true for terminal states', function () {
            expect(GameStatus::COMPLETED->isFinished())->toBeTrue()
                ->and(GameStatus::ABANDONED->isFinished())->toBeTrue();
        });

        test('isFinished returns false for ongoing states', function () {
            expect(GameStatus::PENDING->isFinished())->toBeFalse()
                ->and(GameStatus::ACTIVE->isFinished())->toBeFalse()
                ->and(GameStatus::PAUSED->isFinished())->toBeFalse();
        });
    });
});

describe('LobbyStatus Enum', function () {
    describe('Values', function () {
        test('has all expected statuses', function () {
            $statuses = LobbyStatus::cases();
            
            expect($statuses)->toContainEqual(LobbyStatus::PENDING)
                ->and($statuses)->toContainEqual(LobbyStatus::READY)
                ->and($statuses)->toContainEqual(LobbyStatus::CANCELLED)
                ->and($statuses)->toContainEqual(LobbyStatus::COMPLETED);
        });
    });

    describe('Labels', function () {
        test('returns descriptive labels', function () {
            foreach (LobbyStatus::cases() as $status) {
                expect($status->label())
                    ->toBeString()
                    ->not->toBeEmpty();
            }
        });
    });
});

describe('ActionType Enum', function () {
    describe('Values', function () {
        test('has common action types', function () {
            $types = ActionType::cases();
            
            // Check for some expected action types
            expect($types)->toContain(ActionType::DROP_PIECE)
                ->and($types)->toContain(ActionType::MOVE_PIECE);
        });

        test('action type values are lowercase with underscores', function () {
            foreach (ActionType::cases() as $type) {
                expect($type->value)
                    ->toMatch('/^[a-z_]+$/')
                    ->not->toContain(' ')
                    ->not->toContain('-');
            }
        });
    });

    describe('Labels', function () {
        test('returns human-readable labels', function () {
            foreach (ActionType::cases() as $type) {
                expect($type->label())
                    ->toBeString()
                    ->not->toBeEmpty();
            }
        });

        test('labels are properly formatted', function () {
            expect(ActionType::DROP_PIECE->label())->toBe('Drop Piece')
                ->and(ActionType::MOVE_PIECE->label())->toBe('Move Piece');
        });
    });
});
