<?php

use App\Actions\Game\FindGameByUlidAction;
use App\Actions\Lobby\FindLobbyByUlidAction;
use App\Actions\User\ResolveUsernameAction;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

describe('Invalid and Malformed Input Handling', function () {
    describe('ULID Edge Cases', function () {
        it('handles ULID that is valid format but references non-existent record', function () {
            $action = new FindGameByUlidAction;

            // Valid ULID format but doesn't exist
            expect(fn () => $action->execute('01ARZ3NDEKTSV4RRFFQ69G5FAV', []))
                ->toThrow(ModelNotFoundException::class);
        });
    });

    describe('Special Characters and Encoding', function () {
        it('handles username with emoji characters', function () {
            $user = User::factory()->create();
            $emojiUsername = 'user🎮123';
            $user->update(['username' => $emojiUsername]);

            $action = new ResolveUsernameAction;

            // Should lowercase and handle emoji
            $found = $action->execute($emojiUsername);
            expect($found->id)->toBe($user->id);
        });
    });

    describe('SQL Injection Attempts', function () {
        it('handles SQL injection in username lookup', function () {
            $action = new ResolveUsernameAction;

            $sqlInjection = "admin' OR '1'='1";

            // Should not return any user (parameterized queries protect)
            expect(fn () => $action->execute($sqlInjection))
                ->toThrow(ModelNotFoundException::class);
        });
    });
});
