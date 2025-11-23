<?php

use App\Models\Auth\User;

describe('Invalid and Malformed Input Handling', function () {
    describe('Special Characters and Encoding', function () {
        it('handles username with emoji characters', function () {
            $user = User::factory()->create();
            $emojiUsername = 'user🎮123';
            $user->update(['username' => $emojiUsername]);

            // Should lowercase and handle emoji
            $found = User::withUsername($emojiUsername)->firstOrFail();
            expect($found->id)->toBe($user->id);
        });
    });
});
