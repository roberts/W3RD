<?php

use App\Models\Auth\User;
use Illuminate\Support\Facades\Redis;

describe('Quickplay Matchmaking', function () {
    beforeEach(function () {
        // Mock Redis for PlayerActivityService
        Redis::shouldReceive('setex')->andReturn(true)->byDefault();
        Redis::shouldReceive('get')->andReturn(null)->byDefault();
        Redis::shouldReceive('expire')->andReturn(true)->byDefault();
        Redis::shouldReceive('del')->andReturn(true)->byDefault();
    });

    describe('Authentication', function () {
        test('unauthenticated user cannot access quickplay endpoints', function () {
            $response = $this->postJson('/api/v1/games/quickplay', [
                'game_title' => 'validate-four',
            ]);

            $response->assertStatus(401);
        });
    });
});
