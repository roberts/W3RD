<?php

use App\Actions\Lobby\FindLobbyByUlidAction;
use App\Models\Auth\User;
use App\Models\Game\Lobby;
use App\Models\Game\LobbyPlayer;

describe('FindLobbyByUlidAction', function () {
    describe('Eager Loading', function () {
        it('loads host relationship when requested', function () {
            $host = User::factory()->create();
            $lobby = Lobby::factory()->for($host, 'host')->create();
            $action = new FindLobbyByUlidAction;

            $found = $action->execute($lobby->ulid, ['host']);

            expect($found->relationLoaded('host'))->toBeTrue()
                ->and($found->host->id)->toBe($host->id);
        });

        it('loads players relationship when requested', function () {
            $lobby = Lobby::factory()->create();
            LobbyPlayer::factory()->count(2)->for($lobby)->create();
            $action = new FindLobbyByUlidAction;

            $found = $action->execute($lobby->ulid, ['players']);

            expect($found->relationLoaded('players'))->toBeTrue()
                ->and($found->players)->toHaveCount(2);
        });

        it('loads multiple relationships when requested', function () {
            $host = User::factory()->create();
            $lobby = Lobby::factory()->for($host, 'host')->create();
            LobbyPlayer::factory()->for($lobby)->create();
            $action = new FindLobbyByUlidAction;

            $found = $action->execute($lobby->ulid, ['host', 'players']);

            expect($found->relationLoaded('host'))->toBeTrue()
                ->and($found->relationLoaded('players'))->toBeTrue();
        });

        it('handles complex nested player relationships', function () {
            $lobby = Lobby::factory()->create();
            LobbyPlayer::factory()->for($lobby)->create();
            $action = new FindLobbyByUlidAction;

            $found = $action->execute($lobby->ulid, ['players.user.avatar.image']);

            expect($found->relationLoaded('players'))->toBeTrue()
                ->and($found->players->first()->relationLoaded('user'))->toBeTrue();
        });
    });

    describe('Edge Cases', function () {
        it('handles lobbies with no players', function () {
            $lobby = Lobby::factory()->create();
            $action = new FindLobbyByUlidAction;

            $found = $action->execute($lobby->ulid);

            expect($found->id)->toBe($lobby->id);
        });

        it('handles public lobbies', function () {
            $lobby = Lobby::factory()->create(['is_public' => true]);
            $action = new FindLobbyByUlidAction;

            $found = $action->execute($lobby->ulid);

            expect($found->is_public)->toBeTrue();
        });

        it('handles private lobbies', function () {
            $lobby = Lobby::factory()->create(['is_public' => false]);
            $action = new FindLobbyByUlidAction;

            $found = $action->execute($lobby->ulid);

            expect($found->is_public)->toBeFalse();
        });

        it('returns same lobby instance for multiple calls with same ulid', function () {
            $lobby = Lobby::factory()->create();
            $action = new FindLobbyByUlidAction;

            $found1 = $action->execute($lobby->ulid);
            $found2 = $action->execute($lobby->ulid);

            expect($found1->id)->toBe($found2->id);
        });
    });
});
