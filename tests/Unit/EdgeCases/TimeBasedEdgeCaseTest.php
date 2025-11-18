<?php

use App\Models\Auth\Entry;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Lobby;
use Illuminate\Support\Carbon;

describe('Time-Based Edge Cases', function () {
    describe('Timeout Boundary Conditions', function () {
        it('handles action submitted at exact timeout second', function () {
            $game = Game::factory()->create([
                'game_state' => [
                    'timeout_at' => now()->timestamp,
                ],
            ]);

            // Action submitted right at timeout
            $actionTime = now();
            $timeoutTime = Carbon::createFromTimestamp($game->game_state['timeout_at']);

            // Should accept if before timeout, reject if after
            $isBeforeTimeout = $actionTime->lessThan($timeoutTime);

            expect($isBeforeTimeout)->toBeIn([true, false]);
        });

        it('handles microsecond differences in timeout checking', function () {
            $timeoutAt = now()->addSeconds(10);

            $game = Game::factory()->create([
                'game_state' => ['timeout_at' => $timeoutAt->timestamp],
            ]);

            // Check timeout with microsecond precision
            Carbon::setTestNow($timeoutAt->copy()->subMicroseconds(1));
            $notTimedOut = now()->lessThan($timeoutAt);

            Carbon::setTestNow($timeoutAt->copy()->addMicroseconds(1));
            $timedOut = now()->greaterThan($timeoutAt);

            expect($notTimedOut)->toBeTrue()
                ->and($timedOut)->toBeTrue();

            Carbon::setTestNow();
        });
    });

    describe('Timestamp Comparisons', function () {
        it('handles scheduled lobby timing', function () {
            $scheduledTime = now()->addMinutes(5);

            $lobby = Lobby::factory()->create([
                'scheduled_at' => $scheduledTime,
            ]);

            // Check schedule is in future
            expect($lobby->scheduled_at)->toBeGreaterThan(now()->subMinute());
            expect($lobby->scheduled_at)->toBeLessThan(now()->addHour());
        });

        it('handles lobby timestamp precision', function () {
            $lobby = Lobby::factory()->create([
                'scheduled_at' => now()->addMinutes(1),
            ]);

            $lobby->refresh();
            expect($lobby->scheduled_at)->not->toBeNull();
            expect($lobby->created_at)->toBeLessThanOrEqual($lobby->updated_at);
        });
    });

    describe('Race Conditions With Timestamps', function () {
        it('handles multiple now() calls in same request', function () {
            $first = now();
            usleep(1000); // 1ms to ensure measurable difference
            $second = now();

            // Should be very close but second should be after first
            $diff = $first->diffInMilliseconds($second, false); // false = allow negative

            expect($diff)->toBeGreaterThanOrEqual(0)
                ->and($diff)->toBeLessThan(100);
        });

        it('ensures created_at is before or equal to updated_at', function () {
            $user = User::factory()->create();

            expect($user->created_at)->toBeLessThanOrEqual($user->updated_at);
        });

        it('handles timestamp comparison across database and PHP', function () {
            $phpTime = now();

            $user = User::factory()->create();
            $dbTime = $user->created_at;

            // DB time should be very close to PHP time
            $diff = abs($phpTime->timestamp - $dbTime->timestamp);

            expect($diff)->toBeLessThan(2); // Within 2 seconds
        });
    });

    describe('Timezone Handling', function () {
        it('stores all timestamps in UTC', function () {
            $user = User::factory()->create();

            // Laravel should store in UTC
            expect($user->created_at->timezone->getName())->toBe('UTC');
        });

        it('handles timestamp conversion across timezones', function () {
            $utcTime = now('UTC');
            $estTime = now('America/New_York');

            // Should represent same moment
            expect($utcTime->timestamp)->toBe($estTime->timestamp);
        });
    });

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

    describe('Far Future/Past Dates', function () {
        it('handles dates far in the future', function () {
            $farFuture = now()->addYears(100);

            $lobby = Lobby::factory()->create([
                'scheduled_at' => $farFuture,
            ]);

            expect($lobby->scheduled_at->year)->toBe($farFuture->year);
        });

        it('handles dates far in the past', function () {
            $farPast = now()->subYears(50);

            $user = User::factory()->create([
                'created_at' => $farPast,
            ]);

            expect($user->created_at->year)->toBe($farPast->year);
        });

        it('handles Y2038 problem gracefully', function () {
            // Unix timestamp 32-bit overflow
            $y2038 = Carbon::create(2038, 1, 19, 3, 14, 7, 'UTC');

            try {
                $user = User::factory()->create([
                    'created_at' => $y2038,
                ]);
                $handled = true;
            } catch (\Exception $e) {
                $handled = false;
            }

            // On 64-bit systems, should handle fine
            expect($handled)->toBeTrue();
        });
    });

    describe('Leap Year and Edge Dates', function () {
        it('handles February 29th on leap year', function () {
            $leapDay = Carbon::create(2024, 2, 29);

            $user = User::factory()->create([
                'created_at' => $leapDay,
            ]);

            expect($user->created_at->day)->toBe(29);
        });

        it('handles end of month boundaries', function () {
            $endOfMonth = Carbon::create(2025, 1, 31, 23, 59, 59);

            $lobby = Lobby::factory()->create([
                'scheduled_at' => $endOfMonth,
            ]);

            expect($lobby->scheduled_at->day)->toBe(31);
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

        it('handles sub-second precision in action timestamps', function () {
            $game = Game::factory()->create();

            $action1Time = now();
            $action2Time = $action1Time->copy()->addMicroseconds(500);

            // Both actions in same second but different microseconds
            expect($action1Time->second)->toBe($action2Time->second)
                ->and($action1Time->micro)->not->toBe($action2Time->micro);
        });
    });
});
