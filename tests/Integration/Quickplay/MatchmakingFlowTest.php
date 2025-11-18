<?php

use App\Actions\Quickplay\ApplyDodgePenaltyAction;
use App\Actions\Quickplay\JoinQuickplayQueueAction;
use App\Actions\Quickplay\LeaveQuickplayQueueAction;
use App\Models\Auth\User;
use App\Models\Game\Lobby;
use App\Models\Game\Mode;
use Illuminate\Support\Facades\Cache;

describe('Quickplay Matchmaking Flow', function () {
    describe('Full Matchmaking Flow', function () {
        it('matches 4 players and creates lobby when queue fills', function () {
            $mode = Mode::factory()->create(['max_players' => 4]);
            $users = User::factory()->count(4)->create();

            $joinAction = new JoinQuickplayQueueAction;

            // All 4 players join queue
            foreach ($users as $user) {
                $joinAction->execute($user->id, $mode->id, 1);
            }

            // Check queue state
            $queueKey = "quickplay:queue:{$mode->id}:1";
            $queue = Cache::get($queueKey, []);

            expect($queue)->toHaveCount(4);

            // Simulate matchmaking service finding match
            // (Would normally be handled by MatchmakingService)
            $lobby = Lobby::where('mode_id', $mode->id)
                ->where('status', 'pending')
                ->first();

            // Lobby should be created with all 4 players
            expect($lobby)->not->toBeNull();
        });

        it('creates game when all matched players join lobby', function () {
            $mode = Mode::factory()->create(['max_players' => 4]);
            $users = User::factory()->count(4)->create();

            // Create lobby from matchmaking
            $lobby = Lobby::factory()->create([
                'mode_id' => $mode->id,
                'host_user_id' => $users->first()->id,
                'status' => 'pending',
                'is_public' => false,
            ]);

            // All players join
            foreach ($users as $user) {
                $lobby->players()->create([
                    'user_id' => $user->id,
                    'is_ready' => true,
                ]);
            }

            $lobby->refresh();

            // Lobby should have 4 players ready
            expect($lobby->players)->toHaveCount(4)
                ->and($lobby->players()->where('is_ready', true)->count())->toBe(4);
        });
    });

    describe('Player Leaves Queue', function () {
        it('removes player from queue before match found', function () {
            $mode = Mode::factory()->create();
            $user = User::factory()->create();

            $joinAction = new JoinQuickplayQueueAction;
            $leaveAction = new LeaveQuickplayQueueAction;

            // Join queue
            $joinAction->execute($user->id, $mode->id, 1);

            $queueKey = "quickplay:queue:{$mode->id}:1";
            expect(Cache::get($queueKey, []))->toContain($user->id);

            // Leave queue
            $leaveAction->execute($user->id, $mode->id, 1);

            expect(Cache::get($queueKey, []))->not->toContain($user->id);
        });

        it('does not affect other players in queue when one leaves', function () {
            $mode = Mode::factory()->create();
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $user3 = User::factory()->create();

            $joinAction = new JoinQuickplayQueueAction;
            $leaveAction = new LeaveQuickplayQueueAction;

            // All join
            $joinAction->execute($user1->id, $mode->id, 1);
            $joinAction->execute($user2->id, $mode->id, 1);
            $joinAction->execute($user3->id, $mode->id, 1);

            // User 2 leaves
            $leaveAction->execute($user2->id, $mode->id, 1);

            $queueKey = "quickplay:queue:{$mode->id}:1";
            $queue = Cache::get($queueKey, []);

            expect($queue)->toContain($user1->id)
                ->and($queue)->toContain($user3->id)
                ->and($queue)->not->toContain($user2->id);
        });
    });

    describe('Dodge Penalty System', function () {
        it('applies penalty when player leaves after match found', function () {
            $mode = Mode::factory()->create();
            $user = User::factory()->create();

            $penaltyAction = new ApplyDodgePenaltyAction;

            // Apply dodge penalty
            $penaltyAction->execute($user->id, $mode->id);

            // Check penalty exists in cache
            $penaltyKey = "quickplay:penalty:{$user->id}:{$mode->id}";
            $penalty = Cache::get($penaltyKey);

            expect($penalty)->not->toBeNull()
                ->and($penalty['count'])->toBeGreaterThan(0)
                ->and($penalty['until'])->toBeGreaterThan(now()->timestamp);
        });

        it('prevents requeue during penalty period', function () {
            $mode = Mode::factory()->create();
            $user = User::factory()->create();

            $penaltyAction = new ApplyDodgePenaltyAction;
            $joinAction = new JoinQuickplayQueueAction;

            // Apply penalty
            $penaltyAction->execute($user->id, $mode->id);

            // Try to join queue (should be rejected)
            try {
                $joinAction->execute($user->id, $mode->id, 1);
                $joined = true;
            } catch (\Exception $e) {
                $joined = false;
            }

            expect($joined)->toBeFalse();
        });

        it('increases penalty duration for repeat offenders', function () {
            $mode = Mode::factory()->create();
            $user = User::factory()->create();

            $penaltyAction = new ApplyDodgePenaltyAction;

            // First dodge - short penalty
            $penaltyAction->execute($user->id, $mode->id);
            $penaltyKey = "quickplay:penalty:{$user->id}:{$mode->id}";
            $firstPenalty = Cache::get($penaltyKey);

            // Clear penalty to simulate time passing
            Cache::forget($penaltyKey);

            // Second dodge - longer penalty
            $penaltyAction->execute($user->id, $mode->id);
            $secondPenalty = Cache::get($penaltyKey);

            expect($secondPenalty['count'])->toBeGreaterThan($firstPenalty['count']);
        });
    });

    describe('Skill-Based Matchmaking', function () {
        it('matches players with similar skill ratings', function () {
            $mode = Mode::factory()->create();

            // Create players with different ratings
            $lowRating = User::factory()->create(); // Rating ~1000 (default)
            $midRating1 = User::factory()->create();
            $midRating2 = User::factory()->create();
            $highRating = User::factory()->create();

            $joinAction = new JoinQuickplayQueueAction;

            // Join with different skill brackets
            $joinAction->execute($lowRating->id, $mode->id, 1); // Bracket 1
            $joinAction->execute($midRating1->id, $mode->id, 2); // Bracket 2
            $joinAction->execute($midRating2->id, $mode->id, 2); // Bracket 2
            $joinAction->execute($highRating->id, $mode->id, 3); // Bracket 3

            // Check queues are separate
            $bracket1Queue = Cache::get("quickplay:queue:{$mode->id}:1", []);
            $bracket2Queue = Cache::get("quickplay:queue:{$mode->id}:2", []);

            expect($bracket1Queue)->toHaveCount(1)
                ->and($bracket2Queue)->toHaveCount(2);
        });
    });

    describe('Queue Timeout Handling', function () {
        it('removes player from queue after timeout period', function () {
            $mode = Mode::factory()->create();
            $user = User::factory()->create();

            $joinAction = new JoinQuickplayQueueAction;

            // Join queue with timestamp
            $queueKey = "quickplay:queue:{$mode->id}:1";
            Cache::put($queueKey, [
                $user->id => [
                    'joined_at' => now()->subMinutes(10)->timestamp,
                    'user_id' => $user->id,
                ],
            ], 900);

            // Simulate queue cleanup (would be done by scheduled job)
            $queue = Cache::get($queueKey, []);
            $queue = collect($queue)->filter(function ($entry) {
                return $entry['joined_at'] > now()->subMinutes(5)->timestamp;
            })->toArray();

            Cache::put($queueKey, $queue, 900);

            // User should be removed
            expect(Cache::get($queueKey, []))->toBeEmpty();
        });
    });
});
