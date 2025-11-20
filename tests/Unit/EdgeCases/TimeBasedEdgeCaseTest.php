<?php

use App\Models\Auth\Entry;
use App\Models\Auth\User;
use App\Models\Game\Game;
use Illuminate\Support\Carbon;

describe('Time-Based Edge Cases', function () {
    describe('Entry Timestamp Logic', function () {
        it('sets logged_in_at on creation', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');

            $before = now()->subSecond();

            $entry = Entry::create([
                'user_id' => $user->id,
                'client_id' => 1,
                'token_id' => $token->accessToken->id,
                'logged_in_at' => now(),
            ]);

            $after = now()->addSecond();

            expect($entry->logged_in_at->timestamp)->toBeGreaterThanOrEqual($before->timestamp)
                ->and($entry->logged_in_at->timestamp)->toBeLessThanOrEqual($after->timestamp);
        });

        it('maintains null logged_out_at until logout', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');

            $entry = Entry::create([
                'user_id' => $user->id,
                'client_id' => 1,
                'token_id' => $token->accessToken->id,
                'logged_in_at' => now(),
            ]);

            expect($entry->logged_out_at)->toBeNull();
        });

        it('ensures logged_out_at is after logged_in_at', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');

            $entry = Entry::create([
                'user_id' => $user->id,
                'client_id' => 1,
                'token_id' => $token->accessToken->id,
                'logged_in_at' => now(),
            ]);

            // Simulate logout after some time
            Carbon::setTestNow(now()->addSeconds(5));
            $entry->update(['logged_out_at' => now()]);

            $entry->refresh();

            expect($entry->logged_out_at)->toBeGreaterThan($entry->logged_in_at);

            Carbon::setTestNow();
        });
    });

    describe('Duration Calculations', function () {
        it('calculates game duration accurately', function () {
            $startTime = now();
            Carbon::setTestNow($startTime);

            $game = Game::factory()->create();

            // Simulate game lasting 10 minutes
            Carbon::setTestNow($startTime->copy()->addMinutes(10));

            $game->update(['status' => 'completed']);
            $game->refresh();

            $duration = $game->created_at->diffInMinutes($game->updated_at);

            expect($duration)->toEqual(10);

            Carbon::setTestNow();
        });
    });
});
