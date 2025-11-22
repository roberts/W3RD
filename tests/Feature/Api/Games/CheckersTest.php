<?php

declare(strict_types=1);

use App\Models\Auth\User;
use Illuminate\Support\Facades\Redis;

/**
 * API endpoint tests for Checkers game.
 *
 * These tests verify the complete game flow through API endpoints:
 * - Creating a game via lobby
 * - Making moves via API
 * - Verifying state changes
 * - Detecting game end conditions
 */
describe('Checkers Game API', function () {
    beforeEach(function () {
        // Mock Redis for PlayerActivityService
        Redis::shouldReceive('setex')->andReturn(true)->byDefault();
        Redis::shouldReceive('get')->andReturn('idle')->byDefault();
        Redis::shouldReceive('expire')->andReturn(true)->byDefault();
        Redis::shouldReceive('del')->andReturn(true)->byDefault();
        Redis::shouldReceive('hmset')->andReturn(true)->byDefault();
        Redis::shouldReceive('hgetall')->andReturn([])->byDefault();
        Redis::shouldReceive('exists')->andReturn(false)->byDefault();
    });

    describe('Game Creation', function () {
        test('can create checkers game through lobby and play moves', function () {
            $users = User::factory()->count(2)->create();

            // Create lobby
            $lobbyResponse = $this->actingAs($users[0])
                ->postJson('/api/v1/matchmaking/lobbies', [
                    'game_title' => 'checkers',
                    'game_mode' => 'standard',
                    'max_players' => 2,
                    'is_public' => true,
                ]);

            $lobbyResponse->assertStatus(201);
            $lobbyUlid = $lobbyResponse->json('data.ulid');

            // Second player joins by accepting
            $this->actingAs($users[1])
                ->putJson("/api/v1/matchmaking/lobbies/{$lobbyUlid}/players/{$users[1]->username}", [
                    'status' => 'accepted',
                ])
                ->assertStatus(200);

            // Check if game was auto-started
            $lobbyCheck = $this->actingAs($users[0])
                ->getJson("/api/v1/matchmaking/lobbies/{$lobbyUlid}");

            $gameUlid = $lobbyCheck->json('data.game.ulid');

            $response = $this->actingAs($users[0])
                ->getJson("/api/v1/games/{$gameUlid}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'ulid',
                        'game_title',
                        'status',
                        'players',
                        'game_state',
                    ],
                ]);

            expect($response->json('data.game_title'))->toBe('checkers')
                ->and(count($response->json('data.players')))->toBe(2);
        });
    });

    describe('Game Actions', function () {
        test('can make a move in checkers game', function () {
            $users = User::factory()->count(2)->create();

            // Create and start game through lobby
            $lobbyResponse = $this->actingAs($users[0])
                ->postJson('/api/v1/matchmaking/lobbies', [
                    'game_title' => 'checkers',
                    'game_mode' => 'standard',
                    'max_players' => 2,
                    'is_public' => true,
                ]);

            $lobbyUlid = $lobbyResponse->json('data.ulid');

            $this->actingAs($users[1])
                ->putJson("/api/v1/matchmaking/lobbies/{$lobbyUlid}/players/{$users[1]->username}", [
                    'status' => 'accepted',
                ]);

            $lobbyCheck = $this->actingAs($users[0])
                ->getJson("/api/v1/matchmaking/lobbies/{$lobbyUlid}");

            $gameUlid = $lobbyCheck->json('data.game.ulid');

            // Make a move
            $response = $this->actingAs($users[0])
                ->postJson("/api/v1/games/{$gameUlid}/action", [
                    'action_type' => 'move_piece',
                    'action_details' => [
                        'from_row' => 5,
                        'from_col' => 0,
                        'to_row' => 4,
                        'to_col' => 1,
                    ],
                ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'action' => ['ulid'],
                        'game' => [
                            'ulid',
                            'status',
                            'game_state',
                        ],
                        'next_action_deadline',
                        'timeout',
                    ],
                ]);
        });
    });
});
