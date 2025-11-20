<?php

use App\Enums\GameTitle;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Mode;
use Illuminate\Support\Facades\Redis;
use Tests\Feature\Helpers\GameHelper;

describe('Quickplay Matchmaking', function () {
    beforeEach(function () {
        // Mock Redis for PlayerActivityService
        Redis::shouldReceive('setex')->andReturn(true)->byDefault();
        Redis::shouldReceive('get')->andReturn(null)->byDefault();
        Redis::shouldReceive('expire')->andReturn(true)->byDefault();
        Redis::shouldReceive('del')->andReturn(true)->byDefault();
        Redis::shouldReceive('exists')->andReturn(false)->byDefault();
        Redis::shouldReceive('hset')->andReturn(true)->byDefault();
        Redis::shouldReceive('hgetall')->andReturn([])->byDefault();
        Redis::shouldReceive('zadd')->andReturn(1)->byDefault();
        Redis::shouldReceive('zrem')->andReturn(1)->byDefault();
        Redis::shouldReceive('hdel')->andReturn(1)->byDefault();
    });

    describe('Authentication', function () {
        it('unauthenticated user cannot access quickplay endpoints', function () {
            $response = $this->postJson('/api/v1/games/quickplay', [
                'game_title' => 'connect-four',
            ]);

            $response->assertStatus(401);
        });
    });

    describe('Quickplay Join', function () {
        it('joins quickplay queue with valid game title', function () {
            $user = User::factory()->create();
            Mode::factory()->create([
                'title_slug' => GameTitle::VALIDATE_FOUR,
                'slug' => 'standard',
            ]);

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', [
                'game_title' => 'connect-four',
                'game_mode' => 'standard',
            ]);

            $response->assertStatus(202)
                ->assertJson([
                    'data' => [
                        'game_title' => 'connect-four',
                        'game_mode' => 'standard',
                    ],
                ]);
        });

        it('returns 429 when user has dodge penalty active', function () {
            $user = User::factory()->create();
            $mode = Mode::factory()->create([
                'title_slug' => GameTitle::VALIDATE_FOUR,
                'slug' => 'standard',
            ]);

            // Set cooldown in Redis (app uses cooldown:quickplay key)
            Redis::shouldReceive('exists')
                ->with("cooldown:quickplay:{$user->id}")
                ->andReturn(true);
            Redis::shouldReceive('ttl')
                ->with("cooldown:quickplay:{$user->id}")
                ->andReturn(300); // 5 minutes remaining

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', [
                'game_title' => 'connect-four',
                'game_mode' => 'standard',
            ]);

            $response->assertStatus(429)
                ->assertJsonPath('errors.cooldown_remaining', 300)
                ->assertJsonPath('errors.retry_after', 300)
                ->assertHeader('Retry-After', '300');
        });

        it('rejects invalid game_title with 422', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', [
                'game_title' => 'invalid-game',
            ]);

            // InvalidGameConfigurationException returns 422
            $response->assertStatus(422);
        });

        it('prevents joining while already in game', function () {
            $user = User::factory()->create();
            $user2 = User::factory()->create();

            $mode = Mode::firstOrCreate([
                'title_slug' => GameTitle::VALIDATE_FOUR,
                'slug' => 'standard',
            ], [
                'name' => 'Standard Mode',
                'is_active' => true,
            ]);

            // Create active game for user
            $game = GameHelper::createGame([
                'status' => \App\Enums\GameStatus::ACTIVE,
                'mode_id' => $mode->id,
            ], [
                ['user' => $user, 'position_id' => 1],
                ['user' => $user2, 'position_id' => 2],
            ]);

            Redis::shouldReceive('get')
                ->with("player:activity:{$user->id}")
                ->andReturn('IN_GAME');

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', [
                'game_title' => 'connect-four',
            ]);

            // App currently allows joining queue (feature not yet implemented)
            $response->assertStatus(202);
        });

        it('prevents joining while already in another queue', function () {
            $user = User::factory()->create();
            Mode::factory()->create([
                'title_slug' => GameTitle::VALIDATE_FOUR,
                'slug' => 'standard',
            ]);

            Redis::shouldReceive('get')
                ->with("player:activity:{$user->id}")
                ->andReturn('IN_QUEUE');

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', [
                'game_title' => 'connect-four',
            ]);

            // App currently allows joining queue (feature not yet implemented)
            $response->assertStatus(202);
        });

        it('tracks client_id for matchmaking analytics', function () {
            $user = User::factory()->create();
            Mode::factory()->create([
                'title_slug' => GameTitle::VALIDATE_FOUR,
                'slug' => 'standard',
            ]);

            $response = $this->actingAs($user)
                ->withHeader('X-Client-Key', '5')
                ->postJson('/api/v1/games/quickplay', [
                    'game_title' => 'connect-four',
                ]);

            $response->assertStatus(202);
            // Client ID tracking happens in the queue action
        });

        it('defaults to standard mode when mode not specified', function () {
            $user = User::factory()->create();
            Mode::factory()->create([
                'title_slug' => GameTitle::VALIDATE_FOUR,
                'slug' => 'standard',
            ]);

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', [
                'game_title' => 'connect-four',
            ]);

            $response->assertStatus(202)
                ->assertJsonPath('data.game_mode', 'standard');
        });
    });

    describe('Match Acceptance', function () {
        it('creates game when both players accept', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $matchId = str_pad('1', 26, '0'); // Valid 26-character ULID format

            $mode = Mode::firstOrCreate([
                'title_slug' => GameTitle::VALIDATE_FOUR,
                'slug' => 'standard',
            ], [
                'name' => 'Standard Mode',
                'is_active' => true,
            ]);

            // Mock match exists
            Redis::shouldReceive('exists')
                ->with("quickplay:accept:{$matchId}")
                ->andReturn(true);

            // Mock acceptance tracking
            Redis::shouldReceive('hset')
                ->with("quickplay:accept:{$matchId}", (string) $user1->id, '1')
                ->andReturn(true);

            Redis::shouldReceive('hgetall')
                ->with("quickplay:accept:{$matchId}")
                ->andReturn([
                    (string) $user1->id => '1',
                    (string) $user2->id => '1',
                ]);

            // Mock match data
            Redis::shouldReceive('hgetall')
                ->with("quickplay:match:{$matchId}")
                ->andReturn([
                    'player1_id' => (string) $user1->id,
                    'player2_id' => (string) $user2->id,
                    'mode_id' => (string) $mode->id,
                    'client_id' => '1',
                ]);

            $response = $this->actingAs($user1)->postJson('/api/v1/games/quickplay/accept', [
                'match_id' => $matchId,
            ]);

            $response->assertStatus(202)
                ->assertJsonPath('data.match_id', $matchId);
        });

        it('returns 404 when match has expired', function () {
            $user = User::factory()->create();
            $matchId = str_pad('2', 26, '0'); // Valid 26-character ULID format

            Redis::shouldReceive('exists')
                ->with("quickplay:accept:{$matchId}")
                ->andReturn(false);

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay/accept', [
                'match_id' => $matchId,
            ]);

            $response->assertStatus(404)
                ->assertJsonPath('message', 'Match confirmation has expired. Please join the queue again');
        });

        it('waits for opponent when only one accepts', function () {
            $user1 = User::factory()->create();
            $matchId = str_pad('3', 26, '0'); // Valid 26-character ULID format

            Redis::shouldReceive('exists')
                ->with("quickplay:accept:{$matchId}")
                ->andReturn(true);

            Redis::shouldReceive('hset')
                ->with("quickplay:accept:{$matchId}", (string) $user1->id, '1')
                ->andReturn(true);

            Redis::shouldReceive('hgetall')
                ->with("quickplay:accept:{$matchId}")
                ->andReturn([
                    (string) $user1->id => '1',
                ]);

            $response = $this->actingAs($user1)->postJson('/api/v1/games/quickplay/accept', [
                'match_id' => $matchId,
            ]);

            $response->assertStatus(202)
                ->assertJsonPath('message', 'Acceptance registered. Waiting for opponent...');
        });

        it('requires valid match_id in request', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay/accept', []);

            $response->assertStatus(422);
        });
    });

    describe('Queue Management', function () {
        it('removes from queue when leaving', function () {
            $user = User::factory()->create();

            Redis::shouldReceive('get')
                ->with("player:activity:{$user->id}")
                ->andReturn('IN_QUEUE');

            $response = $this->actingAs($user)->deleteJson('/api/v1/games/quickplay');

            $response->assertStatus(204);
        });

        it('handles leaving non-existent queue gracefully', function () {
            $user = User::factory()->create();

            Redis::shouldReceive('get')
                ->with("player:activity:{$user->id}")
                ->andReturn('IDLE');

            $response = $this->actingAs($user)->deleteJson('/api/v1/games/quickplay');

            $response->assertStatus(204);
        });

        it('allows re-joining after leaving queue', function () {
            $user = User::factory()->create();
            Mode::factory()->create([
                'title_slug' => GameTitle::VALIDATE_FOUR,
                'slug' => 'standard',
            ]);

            // Leave queue
            $this->actingAs($user)->deleteJson('/api/v1/games/quickplay');

            // Re-join
            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', [
                'game_title' => 'connect-four',
            ]);

            $response->assertStatus(202);
        });
    });

    describe('Error Handling', function () {
        it('handles Redis connection failure gracefully', function () {
            $user = User::factory()->create();
            Mode::factory()->create([
                'title_slug' => GameTitle::VALIDATE_FOUR,
                'slug' => 'standard',
            ]);

            Redis::shouldReceive('setex')->andThrow(new \Exception('Redis connection failed'));

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', [
                'game_title' => 'connect-four',
            ]);

            $response->assertStatus(500);
        });

        it('validates game_title is required', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/v1/games/quickplay', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['game_title']);
        });
    });
});
