<?php

use App\Actions\Game\FindGameByUlidAction;
use App\Models\Game\Game;
use App\Models\Game\Mode;
use App\Models\Game\Player;
use Illuminate\Database\Eloquent\ModelNotFoundException;

describe('FindGameByUlidAction', function () {
    describe('Basic Lookup', function () {
        it('finds game by ulid without eager loading', function () {
            $game = Game::factory()->create();
            $action = new FindGameByUlidAction;

            $found = $action->execute($game->ulid);

            expect($found->id)->toBe($game->id)
                ->and($found->ulid)->toBe($game->ulid);
        });

        it('throws ModelNotFoundException for invalid ulid', function () {
            $action = new FindGameByUlidAction;

            $action->execute('invalid-ulid-12345');
        })->throws(ModelNotFoundException::class);

        it('throws ModelNotFoundException for non-existent ulid', function () {
            $action = new FindGameByUlidAction;

            $action->execute('01234567890123456789012345');
        })->throws(ModelNotFoundException::class);
    });

    describe('Eager Loading', function () {
        it('loads mode relationship when requested', function () {
            $mode = Mode::factory()->create();
            $game = Game::factory()->for($mode)->create();
            $action = new FindGameByUlidAction;

            $found = $action->execute($game->ulid, ['mode']);

            expect($found->relationLoaded('mode'))->toBeTrue()
                ->and($found->mode->id)->toBe($mode->id);
        });

        it('loads players relationship when requested', function () {
            $game = Game::factory()->create();
            Player::factory()->for($game)->position(1)->create();
            Player::factory()->for($game)->position(2)->create();
            $action = new FindGameByUlidAction;

            $found = $action->execute($game->ulid, ['players']);

            expect($found->relationLoaded('players'))->toBeTrue()
                ->and($found->players)->toHaveCount(2);
        });

        it('loads nested relationships when requested', function () {
            $game = Game::factory()->create();
            $player = Player::factory()->for($game)->create();
            $action = new FindGameByUlidAction;

            $found = $action->execute($game->ulid, ['players.user']);

            expect($found->relationLoaded('players'))->toBeTrue()
                ->and($found->players->first()->relationLoaded('user'))->toBeTrue()
                ->and($found->players->first()->user->id)->toBe($player->user_id);
        });

        it('loads multiple relationships when requested', function () {
            $mode = Mode::factory()->create();
            $game = Game::factory()->for($mode)->create();
            Player::factory()->for($game)->create();
            $action = new FindGameByUlidAction;

            $found = $action->execute($game->ulid, ['mode', 'players']);

            expect($found->relationLoaded('mode'))->toBeTrue()
                ->and($found->relationLoaded('players'))->toBeTrue();
        });

        it('does not load relationships when empty array provided', function () {
            $game = Game::factory()->create();
            Player::factory()->for($game)->create();
            $action = new FindGameByUlidAction;

            $found = $action->execute($game->ulid, []);

            expect($found->relationLoaded('mode'))->toBeFalse()
                ->and($found->relationLoaded('players'))->toBeFalse();
        });

        it('handles deeply nested relationships', function () {
            $game = Game::factory()->create();
            $player = Player::factory()->for($game)->create();
            $action = new FindGameByUlidAction;

            $found = $action->execute($game->ulid, ['players.user.avatar.image']);

            expect($found->relationLoaded('players'))->toBeTrue()
                ->and($found->players->first()->relationLoaded('user'))->toBeTrue();
        });
    });

    describe('Edge Cases', function () {
        it('handles games with no relationships', function () {
            $game = Game::factory()->create();
            $action = new FindGameByUlidAction;

            $found = $action->execute($game->ulid);

            expect($found->id)->toBe($game->id);
        });

        it('returns same game instance for multiple calls with same ulid', function () {
            $game = Game::factory()->create();
            $action = new FindGameByUlidAction;

            $found1 = $action->execute($game->ulid);
            $found2 = $action->execute($game->ulid);

            expect($found1->id)->toBe($found2->id);
        });
    });
});
