<?php

use App\Actions\Game\FindGameByUlidAction;
use App\Actions\User\ResolveUsernameAction;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

describe('Invalid and Malformed Input Handling', function () {
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
});
