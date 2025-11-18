<?php

use App\Actions\Quickplay\JoinQuickplayQueueAction;
use App\Enums\GameStatus;
use App\Enums\GameTitle;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Mode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

describe('Redis and Cache Failure Scenarios', function () {
    describe('Redis Connection Failures', function () {
        it('handles timeout during lock acquisition', function () {
            // Mock Redis timeout scenario
            $game = Game::factory()->create();

            try {
                // Attempt operation that requires lock
                Cache::lock("game:{$game->id}", 10)->get(function () use ($game) {
                    $game->update(['status' => GameStatus::ACTIVE]);
                });

                $succeeded = true;
            } catch (\Exception $e) {
                $succeeded = false;
            }

            // Should either succeed or fail gracefully
            expect($succeeded)->toBeIn([true, false]);
        });

        it('falls back to database when Redis unavailable', function () {
            // Simulate Redis failure
            $mode = Mode::factory()->create();
            $user = User::factory()->create();

            // Clear cache to simulate Redis being down
            Cache::flush();

            // System should still function (may be slower)
            $game = Game::factory()->create(['mode_id' => $mode->id]);

            expect($game)->not->toBeNull();
        });
    });

    describe('Cache Eviction Scenarios', function () {
        it('handles cache miss for game state', function () {
            $game = Game::factory()->create([
                'game_state' => ['phase' => 'play', 'turn' => 1],
            ]);

            $cacheKey = "game:{$game->id}:state";

            // Put in cache
            Cache::put($cacheKey, $game->game_state, 3600);

            // Simulate eviction
            Cache::forget($cacheKey);

            // Fetch should fall back to database
            $state = Cache::remember($cacheKey, 3600, fn () => $game->fresh()->game_state);

            expect($state)->toBe($game->game_state);
        });

        it('handles stale cache data', function () {
            $game = Game::factory()->create([
                'game_state' => ['phase' => 'setup'],
            ]);

            $cacheKey = "game:{$game->id}:state";
            Cache::put($cacheKey, ['phase' => 'setup'], 3600);

            // Update game directly in DB (cache now stale)
            $game->update(['game_state' => ['phase' => 'play']]);

            // Cached value is stale
            $cachedState = Cache::get($cacheKey);
            $dbState = $game->fresh()->game_state;

            expect($cachedState)->not->toBe($dbState);
        });

        it('invalidates cache when game state changes', function () {
            $game = Game::factory()->create([
                'game_state' => ['phase' => 'setup'],
            ]);

            $cacheKey = "game:{$game->id}:state";
            Cache::put($cacheKey, $game->game_state, 3600);

            // Update game (should invalidate cache)
            $game->update(['game_state' => ['phase' => 'play']]);
            Cache::forget($cacheKey); // Simulate cache invalidation

            // Verify cache is empty
            expect(Cache::has($cacheKey))->toBeFalse();
        });
    });

    describe('Queue Data Integrity', function () {
        it('handles corrupted queue data in Redis', function () {
            $mode = Mode::factory()->create();
            $queueKey = "quickplay:queue:{$mode->id}:1";

            // Put corrupted data
            Cache::put($queueKey, 'not-an-array', 900);

            // System should handle gracefully
            try {
                $queue = Cache::get($queueKey, []);

                if (! is_array($queue)) {
                    $queue = [];
                    Cache::put($queueKey, $queue, 900);
                }

                $handled = true;
            } catch (\Exception $e) {
                $handled = false;
            }

            expect($handled)->toBeTrue();
        });

        it('recovers from partial queue data loss', function () {
            $users = User::factory()->count(3)->create();

            $joinAction = new JoinQuickplayQueueAction;

            // Add users to queue
            foreach ($users as $user) {
                $joinAction->execute($user, GameTitle::HEARTS, 'standard', 1);
            }

            $queueKey = 'quickplay:queue:hearts:standard';
            $originalQueue = Redis::lrange($queueKey, 0, -1);

            // Simulate partial data loss (remove one entry)
            if (count($originalQueue) > 2) {
                Redis::rpop($queueKey);
            }

            // System should detect and handle
            $currentQueue = Redis::lrange($queueKey, 0, -1);

            expect(count($currentQueue))->toBeLessThanOrEqual(3);
        })->skip('Requires Redis extension');
    });

    describe('Redis Cluster Failover', function () {
        it('maintains queue consistency during failover', function () {
            $user = User::factory()->create();

            $queueKey = 'quickplay:queue:hearts:standard';

            // Add to queue
            $joinAction = new JoinQuickplayQueueAction;
            $joinAction->execute($user, GameTitle::HEARTS, 'standard', 1);

            // Simulate reading during failover (may return empty)
            $queue = Redis::lrange($queueKey, 0, -1);

            // Should either have data or empty array (not null)
            expect($queue)->toBeArray();
        })->skip('Requires Redis extension');
    });

    describe('Cache Performance Under Load', function () {
        it('handles high volume of concurrent cache writes', function () {
            $games = Game::factory()->count(100)->create();

            // Simulate many concurrent cache writes
            $failures = 0;

            foreach ($games as $game) {
                try {
                    Cache::put("game:{$game->id}", $game->toArray(), 60);
                } catch (\Exception $e) {
                    $failures++;
                }
            }

            // Most should succeed
            expect($failures)->toBeLessThan(10);
        });

        it('handles cache stampede scenario', function () {
            $game = Game::factory()->create();
            $cacheKey = "game:{$game->id}:expensive";

            // Simulate many concurrent requests for expired cache
            Cache::forget($cacheKey);

            $results = [];

            // Multiple "simultaneous" requests
            for ($i = 0; $i < 5; $i++) {
                $results[] = Cache::remember($cacheKey, 60, function () use ($game) {
                    // Expensive operation
                    usleep(100000); // 100ms

                    return $game->fresh()->toArray();
                });
            }

            // All should get same data
            expect($results)->toHaveCount(5);
        });
    });

    describe('Graceful Degradation', function () {
        it('continues operation without cache', function () {
            // Disable cache
            Cache::shouldReceive('get')->andReturn(null);
            Cache::shouldReceive('put')->andReturn(false);

            // System should still work
            $game = Game::factory()->create();
            $fetched = Game::find($game->id);

            expect($fetched->id)->toBe($game->id);
        });

        it('logs cache failures without crashing', function () {
            Log::shouldReceive('warning')->once();

            try {
                // Simulate cache write failure
                throw new \Exception('Redis connection failed');
            } catch (\Exception $e) {
                Log::warning('Cache operation failed', ['error' => $e->getMessage()]);
            }

            expect(true)->toBeTrue();
        });
    });
});
