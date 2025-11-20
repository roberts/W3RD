<?php

use App\Models\Auth\User;
use App\Models\Game\Lobby;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    // Mock Redis for PlayerActivityService (for API endpoint tests)
    Redis::shouldReceive('setex')->andReturn(true)->byDefault();
    Redis::shouldReceive('get')->andReturn('idle')->byDefault();
    Redis::shouldReceive('expire')->andReturn(true)->byDefault();
    Redis::shouldReceive('del')->andReturn(true)->byDefault();
    Redis::shouldReceive('hmset')->andReturn(true)->byDefault();
    Redis::shouldReceive('hgetall')->andReturn([])->byDefault();
    Redis::shouldReceive('exists')->andReturn(false)->byDefault();
});

describe('Boundary Value Testing', function () {
    describe('Lobby Player Management', function () {
        it('handles lobby with multiple players', function () {
            $lobby = Lobby::factory()->create(['min_players' => 4]);

            // Add multiple players
            for ($i = 0; $i < 4; $i++) {
                $user = User::factory()->create();
                $lobby->players()->create(['user_id' => $user->id]);
            }

            $lobby->refresh();

            expect($lobby->players)->toHaveCount(4);
        });
    });
});
