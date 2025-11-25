<?php

use App\Enums\PlayerActivityState;
use App\GameEngine\Player\PlayerActivityManager;
use App\Jobs\CheckAndCancelPendingProposals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

describe('PlayerActivityManager', function () {
    beforeEach(function () {
        Queue::fake();
        $this->service = app(PlayerActivityManager::class);
        $this->userId = 1;
    });

    describe('state management', function () {
        it('sets and gets player state', function () {
            Redis::shouldReceive('setex')
                ->with("player:{$this->userId}:activity", 1800, 'in_game')
                ->once()
                ->andReturn(true);
            Redis::shouldReceive('get')
                ->with("player:{$this->userId}:activity")
                ->once()
                ->andReturn('in_game');

            $this->service->setState($this->userId, PlayerActivityState::IN_GAME);

            expect($this->service->getState($this->userId))->toBe(PlayerActivityState::IN_GAME);
        });

        it('defaults to OFFLINE when no state set', function () {
            Redis::shouldReceive('get')
                ->with("player:{$this->userId}:activity")
                ->once()
                ->andReturn(null);

            expect($this->service->getState($this->userId))->toBe(PlayerActivityState::OFFLINE);
        });

        it('overwrites previous state', function () {
            Redis::shouldReceive('setex')
                ->with("player:{$this->userId}:activity", 1800, 'in_queue')
                ->once()
                ->andReturn(true);
            Redis::shouldReceive('setex')
                ->with("player:{$this->userId}:activity", 1800, 'in_lobby')
                ->once()
                ->andReturn(true);
            Redis::shouldReceive('get')
                ->with("player:{$this->userId}:activity")
                ->once()
                ->andReturn('in_lobby');

            $this->service->setState($this->userId, PlayerActivityState::IN_QUEUE);
            $this->service->setState($this->userId, PlayerActivityState::IN_LOBBY);

            expect($this->service->getState($this->userId))->toBe(PlayerActivityState::IN_LOBBY);
        });

        it('stores each state as separate Redis key', function () {
            $user1 = 1;
            $user2 = 2;

            Redis::shouldReceive('setex')
                ->with("player:{$user1}:activity", 1800, 'in_game')
                ->once()
                ->andReturn(true);
            Redis::shouldReceive('setex')
                ->with("player:{$user2}:activity", 1800, 'in_queue')
                ->once()
                ->andReturn(true);
            Redis::shouldReceive('get')
                ->with("player:{$user1}:activity")
                ->once()
                ->andReturn('in_game');
            Redis::shouldReceive('get')
                ->with("player:{$user2}:activity")
                ->once()
                ->andReturn('in_queue');

            $this->service->setState($user1, PlayerActivityState::IN_GAME);
            $this->service->setState($user2, PlayerActivityState::IN_QUEUE);

            expect($this->service->getState($user1))->toBe(PlayerActivityState::IN_GAME)
                ->and($this->service->getState($user2))->toBe(PlayerActivityState::IN_QUEUE);
        });
    });

    describe('TTL and expiration', function () {
        it('sets 30 minute TTL on state', function () {
            Redis::shouldReceive('setex')
                ->with("player:{$this->userId}:activity", 1800, 'idle')
                ->once()
                ->andReturn(true);
            Redis::shouldReceive('ttl')
                ->with("player:{$this->userId}:activity")
                ->once()
                ->andReturn(1795);

            $this->service->setState($this->userId, PlayerActivityState::IDLE);

            $ttl = Redis::ttl("player:{$this->userId}:activity");

            expect($ttl)->toBeGreaterThan(1790)
                ->and($ttl)->toBeLessThanOrEqual(1800);
        });

        it('refreshes TTL without changing state', function () {
            $key = "player:{$this->userId}:activity";

            Redis::shouldReceive('setex')
                ->with($key, 1800, 'in_game')
                ->once()
                ->andReturn(true);
            Redis::shouldReceive('exists')
                ->with($key)
                ->once()
                ->andReturn(true);
            Redis::shouldReceive('expire')
                ->with($key, 1800)
                ->once()
                ->andReturn(true);
            Redis::shouldReceive('get')
                ->with($key)
                ->once()
                ->andReturn('in_game');

            $this->service->setState($this->userId, PlayerActivityState::IN_GAME);
            $this->service->refreshActivity($this->userId);

            expect($this->service->getState($this->userId))->toBe(PlayerActivityState::IN_GAME);
        });

        it('does not create state when refreshing non-existent activity', function () {
            Redis::shouldReceive('exists')
                ->with("player:{$this->userId}:activity")
                ->once()
                ->andReturn(false);

            $this->service->refreshActivity($this->userId);

            // No other Redis calls should be made
        });
    });

    describe('availability checks', function () {
        it('returns true for IDLE state', function () {
            Redis::shouldReceive('setex')->once()->andReturn(true);
            Redis::shouldReceive('get')->twice()->andReturn('idle');

            $this->service->setState($this->userId, PlayerActivityState::IDLE);

            expect($this->service->isAvailableForRematch($this->userId))->toBeTrue()
                ->and($this->service->isBusy($this->userId))->toBeFalse();
        });

        it('returns false for IN_GAME state', function () {
            Redis::shouldReceive('setex')->once()->andReturn(true);
            Redis::shouldReceive('get')->twice()->andReturn('in_game');

            $this->service->setState($this->userId, PlayerActivityState::IN_GAME);

            expect($this->service->isAvailableForRematch($this->userId))->toBeFalse()
                ->and($this->service->isBusy($this->userId))->toBeTrue();
        });

        it('returns false for IN_QUEUE state', function () {
            Redis::shouldReceive('setex')->once()->andReturn(true);
            Redis::shouldReceive('get')->twice()->andReturn('in_queue');

            $this->service->setState($this->userId, PlayerActivityState::IN_QUEUE);

            expect($this->service->isAvailableForRematch($this->userId))->toBeFalse()
                ->and($this->service->isBusy($this->userId))->toBeTrue();
        });

        it('returns false for IN_LOBBY state', function () {
            Redis::shouldReceive('setex')->once()->andReturn(true);
            Redis::shouldReceive('get')->twice()->andReturn('in_lobby');

            $this->service->setState($this->userId, PlayerActivityState::IN_LOBBY);

            expect($this->service->isAvailableForRematch($this->userId))->toBeFalse()
                ->and($this->service->isBusy($this->userId))->toBeTrue();
        });

        it('returns false for OFFLINE state', function () {
            Redis::shouldReceive('setex')->once()->andReturn(true);
            Redis::shouldReceive('get')->twice()->andReturn('offline');

            $this->service->setState($this->userId, PlayerActivityState::OFFLINE);

            expect($this->service->isAvailableForRematch($this->userId))->toBeFalse()
                ->and($this->service->isBusy($this->userId))->toBeFalse();
        });
    });

    describe('automatic rematch cancellation', function () {
        it('dispatches cancellation job when state becomes busy', function () {
            Redis::shouldReceive('setex')->once()->andReturn(true);

            $this->service->setState($this->userId, PlayerActivityState::IN_QUEUE);

            Queue::assertPushed(CheckAndCancelPendingProposals::class, function ($job) {
                return $job->userId === $this->userId;
            });
        });

        it('does not dispatch job when setting to IDLE', function () {
            Redis::shouldReceive('setex')->once()->andReturn(true);

            $this->service->setState($this->userId, PlayerActivityState::IDLE);

            Queue::assertNotPushed(CheckAndCancelPendingProposals::class);
        });

        it('does not dispatch job when setting to OFFLINE', function () {
            Redis::shouldReceive('setex')->once()->andReturn(true);

            $this->service->setState($this->userId, PlayerActivityState::OFFLINE);

            Queue::assertNotPushed(CheckAndCancelPendingProposals::class);
        });

        it('dispatches job for all busy states', function () {
            $busyStates = [
                PlayerActivityState::IN_GAME,
                PlayerActivityState::IN_QUEUE,
                PlayerActivityState::IN_LOBBY,
            ];

            foreach ($busyStates as $index => $state) {
                $userId = $index + 1;
                Redis::shouldReceive('setex')->once()->andReturn(true);
                $this->service->setState($userId, $state);
            }

            Queue::assertPushed(CheckAndCancelPendingProposals::class, 3);
        });
    });
});
