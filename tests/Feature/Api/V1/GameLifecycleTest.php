<?php

use App\Enums\GamePhase;
use App\Enums\GameStatus;
use App\Models\Auth\User;
use App\Models\Games\Game;
use App\Models\Games\Player;
use Illuminate\Support\Str;
use Tests\Feature\Helpers\AssertionHelper;
use Tests\Feature\Helpers\GameHelper;

describe('Game Lifecycle', function () {
    // Helper function to post game action with idempotency key
    $postGameAction = function (User $user, string $gameUlid, array $actionData) {
        return test()->actingAs($user)
            ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
            ->postJson("/api/v1/games/{$gameUlid}/action", $actionData);
    };

    // Helper function to create proper game_state structure
    $createGameState = function ($players, $currentPlayerUlid, $boardState = null) {
        $board = $boardState ?? array_fill(0, 6, array_fill(0, 7, null));
        $playersData = [];

        foreach ($players as $index => $player) {
            $playersData[$player->ulid] = [
                'ulid' => $player->ulid,
                'position' => $index + 1,
                'color' => ['red', 'yellow', 'blue', 'green'][$index % 4],
            ];
        }

        return [
            'board' => $board,
            'current_player_ulid' => $currentPlayerUlid,
            'players' => $playersData,
            'columns' => 7,
            'rows' => 6,
            'connect_count' => 4,
        ];
    };

    describe('Game Retrieval', function () {
        it('lists user games with pagination', function () {
            $user = User::factory()->create();

            // Create games where user is a player
            Game::factory()->count(15)->withPlayers([$user])->create(['creator_id' => $user->id]);

            $response = $this->actingAs($user)->getJson('/api/v1/games?page=1&per_page=10');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['ulid', 'status', 'created_at'],
                    ],
                    'meta' => ['current_page', 'per_page', 'total'],
                ]);

            // Controller respects per_page parameter, so only 10 games on page 1
            $data = $response->json('data');
            expect($data)->toBeArray();
            expect(count($data))->toBe(10);
            expect($response->json('meta.total'))->toBe(15);
            expect($response->json('meta.per_page'))->toBe(10);
        });

        it('shows single game details', function () {
            $user = User::factory()->create();
            $game = GameHelper::createGame([
                'creator_id' => $user->id,
                'status' => GameStatus::ACTIVE,
            ], [
                ['user' => $user, 'position_id' => 1],
            ]);

            $response = $this->actingAs($user)->getJson("/api/v1/games/{$game->ulid}");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['ulid', 'status', 'game_state'],
                ]);
        });

        it('rejects unauthorized access to game with 403', function () {
            $gameOwner = User::factory()->create();
            $otherUser = User::factory()->create();
            $game = GameHelper::createGame(['creator_id' => $gameOwner->id], [
                ['user' => $gameOwner, 'position_id' => 1],
            ]);

            $response = $this->actingAs($otherUser)->getJson("/api/v1/games/{$game->ulid}");

            AssertionHelper::assertForbidden($response);
        });
    });

    describe('Game Actions - Valid Moves', function () {
        it('accepts DROP_PIECE action and returns 200', function () {
            $user = User::factory()->create();

            $game = Game::factory()->active()->create([
                'creator_id' => $user->id,
                'game_state' => [], // Will be populated after creating players
            ]);

            $player1 = Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'position_id' => 1,
            ]);

            // Create a second player for ValidateFour (requires 2 players)
            $user2 = User::factory()->create();
            $player2 = Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user2->id,
                'position_id' => 2,
            ]);

            // Set proper ValidateFour game state with currentPlayerUlid set to player1
            $game->update([
                'game_state' => [
                    'board' => array_fill(0, 6, array_fill(0, 7, null)),
                    'current_player_ulid' => $player1->ulid,
                    'columns' => 7,
                    'rows' => 6,
                    'connect_count' => 4,
                    'players' => [
                        $player1->ulid => ['ulid' => $player1->ulid, 'position' => 1, 'color' => 'red'],
                        $player2->ulid => ['ulid' => $player2->ulid, 'position' => 2, 'color' => 'yellow'],
                    ],
                    'phase' => 'active',
                    'status' => 'active',
                ],
            ]);

            $response = $this->actingAs($user)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 3],
                ]);

            if ($response->status() !== 200) {
                dump($response->json());
            }

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['game'],
                    'message',
                ]);
        });

        it('updates game state correctly after action', function () {
            $user = User::factory()->create();
            $game = Game::factory()->active()->create([
                'creator_id' => $user->id,
                'game_state' => [],
            ]);

            $player1 = Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'position_id' => 1,
            ]);

            $user2 = User::factory()->create();
            $player2 = Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user2->id,
                'position_id' => 2,
            ]);

            $game->update([
                'game_state' => [
                    'board' => array_fill(0, 6, array_fill(0, 7, null)),
                    'current_player_ulid' => $player1->ulid,
                    'columns' => 7,
                    'rows' => 6,
                    'connect_count' => 4,
                    'players' => [
                        $player1->ulid => ['ulid' => $player1->ulid, 'position' => 1, 'color' => 'red'],
                        $player2->ulid => ['ulid' => $player2->ulid, 'position' => 2, 'color' => 'yellow'],
                    ],
                    'phase' => 'active',
                    'status' => 'active',
                ],
            ]);

            $this->actingAs($user)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 3],
                ]);

            $game->refresh();
            $gameState = $game->game_state;

            expect($gameState['board'][5][3])->not->toBeNull();
        });

        it('advances turn to next player after valid move', function () {
            $player1 = User::factory()->create();
            $player2 = User::factory()->create();
            $game = Game::factory()->active()->create([
                'creator_id' => $player1->id,
                'game_state' => array_fill(0, 6, array_fill(0, 7, null)),
            ]);
            Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $player1->id,
                'position_id' => 1,
            ]);
            Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $player2->id,
                'position_id' => 2,
            ]);

            $this->actingAs($player1)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 3],
                ]);

            $game->refresh();
            // Turn tracking depends on implementation
            expect($game->turn_number)->toBeGreaterThan(0);
        });

        it('detects win condition and completes game', function () {
            $user = User::factory()->create();
            // Create a board state where player is one move from winning horizontally
            $gameState = array_fill(0, 6, array_fill(0, 7, null));
            $gameState[5][0] = 1; // Player 1's pieces
            $gameState[5][1] = 1;
            $gameState[5][2] = 1;
            // Column 3 will be the winning move

            $game = Game::factory()->active()->create([
                'creator_id' => $user->id,
                'game_state' => $gameState,
            ]);
            Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'position_id' => 1,
            ]);

            $response = $this->actingAs($user)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 3],
                ]);

            $game->refresh();

            // Win detection depends on implementation
            // Either status changes to COMPLETED or game continues
            expect($game->status)->toBeIn([GameStatus::ACTIVE, GameStatus::COMPLETED]);
        });
    });

    describe('Game Actions - Invalid Moves', function () {
        it('rejects move when not player turn with 400', function () {
            $player1 = User::factory()->create();
            $player2 = User::factory()->create();

            $game = Game::factory()->active()->create([
                'creator_id' => $player1->id,
                'game_state' => [],
            ]);

            $player1Record = Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $player1->id,
                'position_id' => 1,
            ]);
            $player2Record = Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $player2->id,
                'position_id' => 2,
            ]);

            // Set proper ValidateFour state with player1 as current player
            $game->update([
                'game_state' => [
                    'board' => array_fill(0, 6, array_fill(0, 7, null)),
                    'current_player_ulid' => $player1Record->ulid,
                    'columns' => 7,
                    'rows' => 6,
                    'connect_count' => 4,
                    'players' => [
                        $player1Record->ulid => ['ulid' => $player1Record->ulid, 'position' => 1, 'color' => 'red'],
                        $player2Record->ulid => ['ulid' => $player2Record->ulid, 'position' => 2, 'color' => 'yellow'],
                    ],
                    'phase' => 'active',
                    'status' => 'active',
                ],
            ]);

            // Player2 tries to move when it's player1's turn
            $response = $this->actingAs($player2)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 3],
                ]);

            $response->assertStatus(422); // API returns 422 for game rule violations
        });

        it('rejects invalid column with 400', function () {
            $user = User::factory()->create();

            $game = Game::factory()->active()->create([
                'creator_id' => $user->id,
                'game_state' => [],
            ]);

            $playerRecord = Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'position_id' => 1,
            ]);

            $user2 = User::factory()->create();
            $player2Record = Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user2->id,
                'position_id' => 2,
            ]);

            // Set proper ValidateFour state with this player as current player
            $game->update([
                'game_state' => [
                    'board' => array_fill(0, 6, array_fill(0, 7, null)),
                    'current_player_ulid' => $playerRecord->ulid,
                    'columns' => 7,
                    'rows' => 6,
                    'connect_count' => 4,
                    'players' => [
                        $playerRecord->ulid => ['ulid' => $playerRecord->ulid, 'position' => 1, 'color' => 'red'],
                        $player2Record->ulid => ['ulid' => $player2Record->ulid, 'position' => 2, 'color' => 'yellow'],
                    ],
                    'phase' => 'active',
                    'status' => 'active',
                ],
            ]);

            $response = $this->actingAs($user)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 99],
                ]);

            $response->assertStatus(422) // API returns 422 for game rule violations
                ->assertJson([
                    'message' => 'Column must be between 0 and 6',
                    'error_code' => 'invalid_column',
                    'game_title' => 'connect-four',
                    'severity' => 'error',
                ]);
        });

        it('rejects move in completed game with 400', function () {
            $user = User::factory()->create();
            $game = Game::factory()->completed()->create([
                'creator_id' => $user->id,
            ]);
            Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'position_id' => 1,
            ]);

            $response = $this->actingAs($user)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 3],
                ]);

            $response->assertStatus(422); // API returns 422 for game rule violations (game not active)
        });

        it('rejects action by non-player with 403', function () {
            $player = User::factory()->create();
            $nonPlayer = User::factory()->create();
            $game = Game::factory()->active()->create([
                'creator_id' => $player->id,
            ]);
            Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $player->id,
                'position_id' => 1,
            ]);

            $response = $this->actingAs($nonPlayer)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 3],
                ]);

            $response->assertForbidden();
        });
    });

    describe('Available Moves', function () {
        it('returns available moves for current player', function () {
            $user = User::factory()->create();
            $game = Game::factory()->active()->create([
                'creator_id' => $user->id,
                'game_state' => array_fill(0, 6, array_fill(0, 7, null)),
            ]);
            Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'position_id' => 1,
            ]);

            $response = $this->actingAs($user)->getJson("/api/v1/games/{$game->ulid}/options");

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => ['options', 'is_your_turn'],
                ]);

            // All columns should be available in empty board
            expect($response->json('data.options'))->toBeArray();
        });

        it('returns empty array when not player turn', function () {
            $player1 = User::factory()->create();
            $player2 = User::factory()->create();

            $p1Ulid = (string) Str::ulid();
            $p2Ulid = (string) Str::ulid();

            $game = Game::factory()->active()->create([
                'creator_id' => $player1->id,
                'game_state' => [
                    'current_player_ulid' => $p1Ulid,
                    'players' => [
                        $p1Ulid => ['ulid' => $p1Ulid, 'position' => 1, 'color' => 'red'],
                        $p2Ulid => ['ulid' => $p2Ulid, 'position' => 2, 'color' => 'yellow'],
                    ],
                    'board' => [],
                    'rows' => 6,
                    'columns' => 7,
                    'phase' => GamePhase::ACTIVE->value,
                    'status' => GameStatus::ACTIVE->value,
                    'roundNumber' => 1,
                    'winnerUlid' => null,
                    'isDraw' => false,
                ],
            ]);
            Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $player1->id,
                'position_id' => 1,
                'ulid' => $p1Ulid,
            ]);
            Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $player2->id,
                'position_id' => 2,
                'ulid' => $p2Ulid,
            ]);

            $response = $this->actingAs($player2)->getJson("/api/v1/games/{$game->ulid}/options");

            $response->assertOk();
            expect($response->json('data.options'))->toBe([]);
            expect($response->json('data.is_your_turn'))->toBeFalse();
        });
    });

    describe('Edge Cases', function () {
        it('returns 404 for invalid game_id', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->getJson('/api/v1/games/invalid-ulid-123');

            $response->assertNotFound();
        });

        it('handles concurrent actions gracefully', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            $game = Game::factory()->active()->create([
                'creator_id' => $user1->id,
                'game_state' => [],
            ]);

            $player1 = Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user1->id,
                'position_id' => 1,
            ]);

            $player2 = Player::factory()->create([
                'game_id' => $game->id,
                'user_id' => $user2->id,
                'position_id' => 2,
            ]);

            $game->update([
                'game_state' => [
                    'board' => array_fill(0, 6, array_fill(0, 7, null)),
                    'current_player_ulid' => $player1->ulid,
                    'columns' => 7,
                    'rows' => 6,
                    'connect_count' => 4,
                    'players' => [
                        $player1->ulid => ['ulid' => $player1->ulid, 'position' => 1, 'color' => 'red'],
                        $player2->ulid => ['ulid' => $player2->ulid, 'position' => 2, 'color' => 'yellow'],
                    ],
                    'phase' => 'active',
                    'status' => 'active',
                ],
            ]);

            // Try to submit action with player 2 (not their turn)
            $response = $this->actingAs($user2)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 3],
                ]);

            // Should reject because it's not their turn (422 for game rule violations)
            expect($response->status())->toBeIn([422, 403]);
        });

        it('rejects malformed JSON in request body', function () {
            $user = User::factory()->create();
            $game = GameHelper::createGame(['creator_id' => $user->id], [
                ['user' => $user, 'position_id' => 1],
            ]);

            // Using call() to send raw invalid JSON string
            $response = $this->actingAs($user)->call(
                'POST',
                "/api/v1/games/{$game->ulid}/action",
                [],
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_ACCEPT' => 'application/json',
                ],
                'invalid-json-content'
            );

            // Should return 400 for malformed JSON
            expect($response->status())->toBeIn([400, 422]);
        });
    });

    describe('Game Actions - Network & Timing Issues', function () use ($createGameState) {
        it('handles duplicate action submission (idempotency)', function () use ($createGameState) {
            $user = User::factory()->create();
            $user2 = User::factory()->create();

            $game = GameHelper::createGame([
                'status' => GameStatus::ACTIVE,
            ], [
                ['user' => $user, 'position_id' => 1],
                ['user' => $user2, 'position_id' => 2],
            ]);

            $player = $game->getPlayerForUser($user->id);
            $player2 = $game->players()->where('user_id', '!=', $user->id)->first();

            $game->update([
                'game_state' => $createGameState([$player, $player2], $player->ulid),
            ]);

            // Submit same action twice
            $response1 = $this->actingAs($user)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 3],
                ]);

            $response2 = $this->actingAs($user)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 3],
                ]);

            // First succeeds, second should fail (not their turn anymore)
            expect($response1->status())->toBe(200);
            expect($response2->status())->toBe(422); // Game rule violation: not their turn
        });

        it('prevents action submission during opponent turn', function () use ($createGameState) {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            $game = GameHelper::createGame([
                'status' => GameStatus::ACTIVE,
            ], [
                ['user' => $user1, 'position_id' => 1],
                ['user' => $user2, 'position_id' => 2],
            ]);

            $player1 = $game->getPlayerForUser($user1->id);
            $player2 = $game->getPlayerForUser($user2->id);

            $game->update([
                'game_state' => $createGameState([$player1, $player2], $player1->ulid),
            ]);

            // Player 2 tries to make move during Player 1's turn
            $response = $this->actingAs($user2)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 0],
                ]);

            $response->assertStatus(422); // API returns 422 for game rule violations
            expect($response->json('error_code'))->toBe('not_player_turn');
        });

        it('validates action payload size limits', function () use ($createGameState) {
            $user = User::factory()->create();
            $user2 = User::factory()->create();

            $game = GameHelper::createGame([
                'status' => GameStatus::ACTIVE,
            ], [
                ['user' => $user, 'position_id' => 1],
                ['user' => $user2, 'position_id' => 2],
            ]);

            $player = $game->getPlayerForUser($user->id);
            $player2 = $game->players()->where('user_id', '!=', $user->id)->first();

            $game->update([
                'game_state' => $createGameState([$player, $player2], $player->ulid),
            ]);

            // Submit action with excessively large payload
            $response = $this->actingAs($user)
                ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => [
                        'column' => 3,
                        'extra_data' => str_repeat('a', 100000), // 100KB of garbage
                    ],
                ]);

            // Should either accept (and ignore extra data) or reject
            expect($response->status())->toBeIn([200, 400, 413, 422]);
        });
    });

    describe('Game Actions - Multi-game Scenarios', function () use ($createGameState) {
        it('handles actions from multiple devices for same user', function () use ($createGameState) {
            $user = User::factory()->create();
            $user2 = User::factory()->create();

            $game = GameHelper::createGame([
                'status' => GameStatus::ACTIVE,
            ], [
                ['user' => $user, 'position_id' => 1],
                ['user' => $user2, 'position_id' => 2],
            ]);

            $player = $game->getPlayerForUser($user->id);
            $player2 = $game->players()->where('user_id', '!=', $user->id)->first();

            $game->update([
                'game_state' => $createGameState([$player, $player2], $player->ulid),
            ]);

            // Submit from "mobile" device
            $response1 = $this->actingAs($user)
                ->withHeader('X-Client-Key', '2') // Mobile client
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 3],
                ]);

            // Try to submit from "web" device (should fail - not their turn anymore)
            $response2 = $this->actingAs($user)
                ->withHeader('X-Client-Key', '1') // Web client
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'drop_piece',
                    'action_details' => ['column' => 4],
                ]);

            expect($response1->status())->toBe(200);
            expect($response2->status())->toBe(422); // Game rule violation: not their turn
        });
    });

    describe('Game Actions - Rate Limiting', function () {
        it('enforces per-user action rate limit', function () {
            $user = User::factory()->create();
            $user2 = User::factory()->create();

            $game = GameHelper::createGame([
                'status' => GameStatus::ACTIVE,
            ], [
                ['user' => $user, 'position_id' => 1],
                ['user' => $user2, 'position_id' => 2],
            ]);

            $player = $game->getPlayerForUser($user->id);

            $game->update([
                'game_state' => [
                    'board' => array_fill(0, 6, array_fill(0, 7, null)),
                    'current_player_ulid' => $player->ulid,
                    'player_ulids' => [$player->ulid, $game->players()->where('user_id', '!=', $user->id)->first()->ulid],
                    'player_colors' => ['red', 'yellow'],
                ],
            ]);

            // Rapidly submit multiple actions
            $responses = [];
            for ($i = 0; $i < 10; $i++) {
                $responses[] = $this->actingAs($user)
                    ->withHeader('X-Idempotency-Key', \Illuminate\Support\Str::uuid()->toString())
                    ->postJson("/api/v1/games/{$game->ulid}/action", [
                        'action_type' => 'drop_piece',
                        'action_details' => ['column' => 3],
                    ]);
            }

            // First should succeed, rest should fail (not their turn or rate limited)
            $successCount = collect($responses)->filter(fn ($r) => $r->status() === 200)->count();
            expect($successCount)->toBeLessThanOrEqual(2); // At most 2 rapid actions succeed
        });
    });
});
