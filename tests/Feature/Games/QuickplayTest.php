<?php

use App\Models\Auth\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

describe('Quickplay Matchmaking', function () {
    describe('Queue Management', function () {
        test('authenticated user can join quickplay queue', function () {
            $user = User::factory()->create();

            // Mock Redis responses
            Redis::shouldReceive('exists')->andReturn(false);
            Redis::shouldReceive('zadd')->andReturn(1);
            Redis::shouldReceive('hset')->andReturn(1);
            Redis::shouldReceive('zscore')->with('quickplay:validate-four:standard', $user->id)->andReturn(1.0);

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', [
                'game_title' => 'validate-four',
            ]);

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Successfully joined the queue',
                    'game_title' => 'validate-four',
                ]);
        });

        test('user can join quickplay queue with game mode', function () {
            $user = User::factory()->create();

            // Mock Redis responses
            Redis::shouldReceive('exists')->andReturn(false);
            Redis::shouldReceive('zadd')->andReturn(1);
            Redis::shouldReceive('hset')->andReturn(1);
            Redis::shouldReceive('zscore')->with('quickplay:validate-four:blitz', $user->id)->andReturn(1.0);

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', [
                'game_title' => 'validate-four',
                'game_mode' => 'blitz',
            ]);

            $response->assertStatus(202)
                ->assertJson([
                    'game_mode' => 'blitz',
                ]);
        });

        test('user cannot join queue with invalid game title', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', [
                'game_title' => 'invalid-game',
            ]);

            $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Invalid game title',
                ]);
        });

        test('user can leave quickplay queue', function () {
            $user = User::factory()->create();

            // Mock Redis responses for leave
            Redis::shouldReceive('zrem')->andReturn(1);
            Redis::shouldReceive('hdel')->andReturn(1);
            Redis::shouldReceive('zscore')->andReturn(null);

            $response = $this->actingAs($user)->deleteJson('/api/v1/games/quickplay');

            $response->assertStatus(204);
        });

        test('user on cooldown cannot join queue', function () {
            $user = User::factory()->create();

            // Mock cooldown exists - this needs to be first!
            Redis::shouldReceive('exists')
                ->with("cooldown:quickplay:{$user->id}")
                ->once()
                ->andReturn(true);
            Redis::shouldReceive('ttl')
                ->with("cooldown:quickplay:{$user->id}")
                ->once()
                ->andReturn(45);

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', [
                'game_title' => 'validate-four',
            ]);

            $response->assertStatus(429)
                ->assertJsonStructure([
                    'error',
                    'cooldown_remaining',
                ]);
        });
    });

    describe('Match Acceptance', function () {
        test('user can accept a match', function () {
            $user = User::factory()->create();
            $matchId = (string) Str::ulid();
            $confirmKey = "quickplay:accept:{$matchId}";

            // Mock Redis responses for match acceptance
            Redis::shouldReceive('exists')
                ->with($confirmKey)
                ->once()
                ->andReturn(true);
            Redis::shouldReceive('hset')
                ->with($confirmKey, $user->id, '1')
                ->once()
                ->andReturn(1);
            Redis::shouldReceive('hgetall')
                ->with($confirmKey)
                ->once()
                ->andReturn([
                    (string) $user->id => '1',
                    '999' => '0',
                ]);

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay/accept', [
                'match_id' => $matchId,
            ]);

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Acceptance registered. Waiting for opponent...',
                ]);
        });

        test('user cannot accept expired match', function () {
            $user = User::factory()->create();
            $expiredMatchId = (string) Str::ulid();

            // Mock match doesn't exist
            Redis::shouldReceive('exists')
                ->with("quickplay:accept:{$expiredMatchId}")
                ->once()
                ->andReturn(false);

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay/accept', [
                'match_id' => $expiredMatchId,
            ]);

            $response->assertStatus(404)
                ->assertJson([
                    'error' => 'Match confirmation has expired',
                ]);
        });
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
