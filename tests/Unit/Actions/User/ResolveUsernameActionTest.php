<?php

use App\Actions\User\ResolveUsernameAction;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

describe('ResolveUsernameAction', function () {
    describe('execute() Method', function () {
        it('resolves existing username to user', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'testuser']);
            $action = new ResolveUsernameAction;

            $found = $action->execute('testuser');

            expect($found->id)->toBe($user->id)
                ->and($found->username)->toBe('testuser');
        });

        it('converts username to lowercase before lookup', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'testuser']);
            $action = new ResolveUsernameAction;

            $found = $action->execute('TestUser');

            expect($found->id)->toBe($user->id);
        });

        it('handles all uppercase username', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'testuser']);
            $action = new ResolveUsernameAction;

            $found = $action->execute('TESTUSER');

            expect($found->id)->toBe($user->id);
        });

        it('handles mixed case username', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'testuser']);
            $action = new ResolveUsernameAction;

            $found = $action->execute('TeStUsEr');

            expect($found->id)->toBe($user->id);
        });

        it('throws ModelNotFoundException for non-existent username', function () {
            $action = new ResolveUsernameAction;

            $action->execute('nonexistent');
        })->throws(ModelNotFoundException::class);

        it('throws ModelNotFoundException for empty string', function () {
            $action = new ResolveUsernameAction;

            $action->execute('');
        })->throws(ModelNotFoundException::class);
    });

    describe('executeOrNull() Method', function () {
        it('returns user for existing username', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'testuser']);
            $action = new ResolveUsernameAction;

            $found = $action->executeOrNull('testuser');

            expect($found)->not->toBeNull()
                ->and($found->id)->toBe($user->id);
        });

        it('returns null for non-existent username', function () {
            $action = new ResolveUsernameAction;

            $result = $action->executeOrNull('nonexistent');

            expect($result)->toBeNull();
        });

        it('converts username to lowercase before lookup', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'testuser']);
            $action = new ResolveUsernameAction;

            $found = $action->executeOrNull('TestUser');

            expect($found)->not->toBeNull()
                ->and($found->id)->toBe($user->id);
        });

        it('returns null for empty string', function () {
            $action = new ResolveUsernameAction;

            $result = $action->executeOrNull('');

            expect($result)->toBeNull();
        });

        it('handles all uppercase username', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'testuser']);
            $action = new ResolveUsernameAction;

            $found = $action->executeOrNull('TESTUSER');

            expect($found)->not->toBeNull()
                ->and($found->id)->toBe($user->id);
        });
    });

    describe('Username Formats', function () {
        it('handles auto-generated usernames', function () {
            $user = User::factory()->create(); // Uses default pattern
            $action = new ResolveUsernameAction;

            $found = $action->execute($user->username);

            expect($found->id)->toBe($user->id);
        });

        it('handles usernames with numbers', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'user12345']);
            $action = new ResolveUsernameAction;

            $found = $action->execute('user12345');

            expect($found->id)->toBe($user->id);
        });

        it('handles usernames with underscores', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'test_user']);
            $action = new ResolveUsernameAction;

            $found = $action->execute('test_user');

            expect($found->id)->toBe($user->id);
        });

        it('handles usernames with hyphens', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'test-user']);
            $action = new ResolveUsernameAction;

            $found = $action->execute('test-user');

            expect($found->id)->toBe($user->id);
        });
    });

    describe('Edge Cases', function () {
        it('finds correct user when multiple users exist', function () {
            $user1 = User::factory()->create();
            $user1->update(['username' => 'user1']);

            $targetUser = User::factory()->create();
            $targetUser->update(['username' => 'user2']);

            $user3 = User::factory()->create();
            $user3->update(['username' => 'user3']);

            $action = new ResolveUsernameAction;

            $found = $action->execute('user2');

            expect($found->id)->toBe($targetUser->id);
        });

        it('handles single character username', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'a']);
            $action = new ResolveUsernameAction;

            $found = $action->execute('a');

            expect($found->id)->toBe($user->id);
        });

        it('handles long username', function () {
            $longUsername = str_repeat('a', 50);
            $user = User::factory()->create();
            $user->update(['username' => $longUsername]);
            $action = new ResolveUsernameAction;

            $found = $action->execute($longUsername);

            expect($found->id)->toBe($user->id);
        });

        it('distinguishes between similar usernames', function () {
            $user1 = User::factory()->create();
            $user1->update(['username' => 'testuser']);

            $user2 = User::factory()->create();
            $user2->update(['username' => 'testuser2']);

            $action = new ResolveUsernameAction;

            $found1 = $action->execute('testuser');
            $found2 = $action->execute('testuser2');

            expect($found1->id)->toBe($user1->id)
                ->and($found2->id)->toBe($user2->id)
                ->and($found1->id)->not->toBe($found2->id);
        });
    });

    describe('Database Interaction', function () {
        it('returns same user on repeated calls', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'testuser']);
            $action = new ResolveUsernameAction;

            $found1 = $action->execute('testuser');
            $found2 = $action->execute('testuser');

            expect($found1->id)->toBe($found2->id);
        });

        it('executes case-insensitive query', function () {
            $user = User::factory()->create();
            $user->update(['username' => 'mixedcase']);
            $action = new ResolveUsernameAction;

            // All of these should find the same user
            $lower = $action->execute('mixedcase');
            $upper = $action->execute('MIXEDCASE');
            $mixed = $action->execute('MiXeDcAsE');

            expect($lower->id)->toBe($user->id)
                ->and($upper->id)->toBe($user->id)
                ->and($mixed->id)->toBe($user->id);
        });
    });
});
