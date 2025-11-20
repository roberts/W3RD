<?php

use App\Actions\Game\FindGameByUlidAction;
use App\Models\Game\Game;
use App\Models\Game\Mode;
use App\Models\Game\Player;
use Illuminate\Database\Eloquent\ModelNotFoundException;

describe('FindGameByUlidAction', function () {
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
            $game = Game::factory()->withPlayers(2)->create();
            $action = new FindGameByUlidAction;

            $found = $action->execute($game->ulid, ['players']);

            expect($found->relationLoaded('players'))->toBeTrue()
                ->and($found->players)->toHaveCount(2);
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
    });
});
