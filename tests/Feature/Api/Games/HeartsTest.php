<?php

declare(strict_types=1);

use App\Models\Auth\User;
use App\Models\Games\Mode;
use App\Models\Games\Player;
use Illuminate\Support\Facades\Redis;

/**
 * API endpoint tests for Hearts game.
 *
 * These tests verify the complete game flow through API endpoints:
 * - Creating a 4-player Hearts game via lobby
 * - Passing cards in the pass phase
 * - Playing cards during trick-taking
 * - Verifying proper game state management
 */
describe('Hearts Game API', function () {
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
        test('can create hearts game through lobby', function () {
            $users = User::factory()->count(4)->create();

            // Create a mode for hearts
            $mode = Mode::factory()->create([
                'title_slug' => 'hearts',
                'slug' => 'standard',
            ]);

            // Create lobby
            $lobbyResponse = $this->actingAs($users[0])
                ->postJson('/api/v1/matchmaking/lobbies', [
                    'game_title' => 'hearts',
                    'mode_id' => $mode->id,
                    'max_players' => 4,
                    'is_public' => true,
                ]);

            $lobbyResponse->assertStatus(201);
            $lobbyUlid = $lobbyResponse->json('data.ulid');

            // Other players join
            foreach ([$users[1], $users[2], $users[3]] as $user) {
                $this->actingAs($user)
                    ->putJson("/api/v1/matchmaking/lobbies/{$lobbyUlid}/players/{$user->username}", [
                        'status' => 'accepted',
                    ])
                    ->assertStatus(200);
            }

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

            expect($response->json('data.game_title'))->toBe('hearts')
                ->and(count($response->json('data.players')))->toBe(4);
        });

        test('hearts game requires exactly 4 players', function () {
            $users = User::factory()->count(3)->create();

            // Create a mode for hearts
            $mode = Mode::factory()->create([
                'title_slug' => 'hearts',
                'slug' => 'standard',
            ]);

            // Create lobby with 4 max players
            $lobbyResponse = $this->actingAs($users[0])
                ->postJson('/api/v1/matchmaking/lobbies', [
                    'game_title' => 'hearts',
                    'mode_id' => $mode->id,
                    'min_players' => 4,
                    'max_players' => 4,
                    'is_public' => true,
                ]);

            $lobbyResponse->assertStatus(201);
            $lobbyUlid = $lobbyResponse->json('data.ulid');

            // Only 2 other players join (3 total, need 4)
            foreach ([$users[1], $users[2]] as $user) {
                $this->actingAs($user)
                    ->putJson("/api/v1/matchmaking/lobbies/{$lobbyUlid}/players/{$user->username}", [
                        'status' => 'accepted',
                    ])
                    ->assertStatus(200);
            }

            // Try to start game with only 3 players - should fail
            $lobbyCheck = $this->actingAs($users[0])
                ->getJson("/api/v1/matchmaking/lobbies/{$lobbyUlid}");

            // Game should not be created (game.ulid should be null)
            expect($lobbyCheck->json('data.lobby.status'))->toBe('pending')
                ->and($lobbyCheck->json('data.game'))->toBeNull();
        });
    });

    describe('Game Actions', function () {
        describe('Pass Cards Phase', function () {
            test('can pass cards in hearts game', function () {
                $users = User::factory()->count(4)->create();

                // Create a mode for hearts
                $mode = Mode::factory()->create([
                    'title_slug' => 'hearts',
                    'slug' => 'standard',
                ]);

                // Create lobby and start game
                $lobbyResponse = $this->actingAs($users[0])
                    ->postJson('/api/v1/matchmaking/lobbies', [
                        'game_title' => 'hearts',
                        'mode_id' => $mode->id,
                        'max_players' => 4,
                        'is_public' => true,
                    ]);

                $lobbyUlid = $lobbyResponse->json('data.ulid');

                foreach ([$users[1], $users[2], $users[3]] as $user) {
                    $this->actingAs($user)
                        ->putJson("/api/v1/matchmaking/lobbies/{$lobbyUlid}/players/{$user->username}", [
                            'status' => 'accepted',
                        ]);
                }

                $lobbyCheck = $this->actingAs($users[0])
                    ->getJson("/api/v1/matchmaking/lobbies/{$lobbyUlid}");

                $gameUlid = $lobbyCheck->json('data.game.ulid');

                // Deal cards
                $this->actingAs($users[0])
                    ->postJson("/api/v1/games/{$gameUlid}/action", [
                        'action_type' => 'deal_cards',
                        'action_details' => ['confirm' => true],
                    ])->assertStatus(200);

                // Get game state to find current player (who has C2)
                $gameResponse = $this->actingAs($users[0])
                    ->getJson("/api/v1/games/{$gameUlid}")
                    ->assertStatus(200);

                $gameState = $gameResponse->json('data.game_state');
                $currentPlayerUlid = $gameState['currentPlayerUlid'];

                // Find the user object for current player
                $player = Player::withUlid($currentPlayerUlid)->first();
                $currentUser = $player->user;

                // Pass cards (using current player to pass authorization)
                $response = $this->actingAs($currentUser)
                    ->postJson("/api/v1/games/{$gameUlid}/action", [
                        'action_type' => 'pass_cards',
                        'action_details' => [
                            'cards' => ['H2', 'H3', 'H4'],
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

        describe('Playing Cards', function () {
            test('can play card in hearts game', function () {
                $users = User::factory()->count(4)->create();

                // Create a mode for hearts
                $mode = Mode::factory()->create([
                    'title_slug' => 'hearts',
                    'slug' => 'standard',
                ]);

                // Create and start game
                $lobbyResponse = $this->actingAs($users[0])
                    ->postJson('/api/v1/matchmaking/lobbies', [
                        'game_title' => 'hearts',
                        'mode_id' => $mode->id,
                        'max_players' => 4,
                        'is_public' => true,
                    ]);

                $lobbyUlid = $lobbyResponse->json('data.ulid');

                foreach ([$users[1], $users[2], $users[3]] as $user) {
                    $this->actingAs($user)
                        ->putJson("/api/v1/matchmaking/lobbies/{$lobbyUlid}/players/{$user->username}", [
                            'status' => 'accepted',
                        ]);
                }

                $lobbyCheck = $this->actingAs($users[0])
                    ->getJson("/api/v1/matchmaking/lobbies/{$lobbyUlid}");

                $gameUlid = $lobbyCheck->json('data.game.ulid');

                // Deal cards
                $this->actingAs($users[0])
                    ->postJson("/api/v1/games/{$gameUlid}/action", [
                        'action_type' => 'deal_cards',
                        'action_details' => ['confirm' => true],
                    ])->assertStatus(200);

                // Get game state to find current player
                $gameResponse = $this->actingAs($users[0])
                    ->getJson("/api/v1/games/{$gameUlid}")
                    ->assertStatus(200);

                $gameState = $gameResponse->json('data.game_state');
                $currentPlayerUlid = $gameState['currentPlayerUlid'];

                // Find the user object for current player
                $player = Player::withUlid($currentPlayerUlid)->first();
                $currentUser = $player->user;

                // Play a card (C2 is required to start)
                $response = $this->actingAs($currentUser)
                    ->postJson("/api/v1/games/{$gameUlid}/action", [
                        'action_type' => 'play_card',
                        'action_details' => [
                            'card' => 'C2',
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
});
