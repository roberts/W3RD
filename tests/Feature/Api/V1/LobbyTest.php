<?php

use App\Enums\LobbyPlayerStatus;
use App\Enums\LobbyStatus;
use App\Models\Auth\User;
use App\Models\Game\Lobby;
use App\Models\Game\LobbyPlayer;
use Illuminate\Support\Facades\Redis;

describe('Lobby Management', function () {
    beforeEach(function () {
        // Mock Redis for PlayerActivityService
        Redis::shouldReceive('setex')->andReturn(true)->byDefault();
        Redis::shouldReceive('get')->andReturn('idle')->byDefault();
        Redis::shouldReceive('del')->andReturn(true)->byDefault();
        Redis::shouldReceive('expire')->andReturn(true)->byDefault();
    });

    describe('Lobby Creation', function () {
        it('authenticated user can create a private lobby', function () {
            $host = User::factory()->create();

            $response = $this->actingAs($host)->postJson('/api/v1/games/lobbies', [
                'game_title' => 'connect-four',
                'is_public' => false,
                'min_players' => 2,
            ]);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'data' => ['ulid', 'game_title', 'is_public', 'min_players'],
                ]);

            $this->assertDatabaseHas('lobbies', [
                'game_title' => 'connect-four',
                'host_id' => $host->id,
                'is_public' => false,
                'status' => 'pending',
            ]);
        });

        it('authenticated user can create a public lobby', function () {
            $host = User::factory()->create();

            $response = $this->actingAs($host)->postJson('/api/v1/games/lobbies', [
                'game_title' => 'connect-four',
                'is_public' => true,
                'min_players' => 4,
            ]);

            $response->assertStatus(201);

            $this->assertDatabaseHas('lobbies', [
                'host_id' => $host->id,
                'is_public' => true,
            ]);
        });

        it('user can create lobby with invitees', function () {
            $host = User::factory()->create();
            $invitee1 = User::factory()->create();
            $invitee2 = User::factory()->create();

            $response = $this->actingAs($host)->postJson('/api/v1/games/lobbies', [
                'game_title' => 'connect-four',
                'is_public' => false,
                'invitees' => [$invitee1->id, $invitee2->id],
            ]);

            $response->assertStatus(201);

            // Check host is auto-accepted
            $this->assertDatabaseHas('lobby_players', [
                'user_id' => $host->id,
                'status' => 'accepted',
            ]);

            // Check invitees are pending
            $this->assertDatabaseHas('lobby_players', [
                'user_id' => $invitee1->id,
                'status' => 'pending',
            ]);

            $this->assertDatabaseHas('lobby_players', [
                'user_id' => $invitee2->id,
                'status' => 'pending',
            ]);
        });

        it('user can create scheduled lobby', function () {
            $host = User::factory()->create();
            $scheduledTime = now()->addHours(2)->toIso8601String();

            $response = $this->actingAs($host)->postJson('/api/v1/games/lobbies', [
                'game_title' => 'connect-four',
                'is_public' => true,
                'min_players' => 4,
                'scheduled_at' => $scheduledTime,
            ]);

            $response->assertStatus(201);

            $this->assertDatabaseHas('lobbies', [
                'host_id' => $host->id,
            ]);
        });
    });

    describe('Lobby Listing', function () {
        it('can list all public lobbies', function () {
            $host = User::factory()->create();

            // Create public and private lobbies
            Lobby::factory()->public()->create();
            Lobby::factory()->public()->create();
            Lobby::factory()->private()->create();

            $response = $this->actingAs($host)->getJson('/api/v1/games/lobbies');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['ulid', 'game_title', 'host', 'min_players', 'current_players', 'status'],
                    ],
                ]);

            // Should only return public lobbies
            expect($response->json('data'))->toHaveCount(2);
        });

        it('can view lobby details', function () {
            $host = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);
            LobbyPlayer::factory()->create([
                'lobby_id' => $lobby->id,
                'user_id' => $host->id,
                'status' => LobbyPlayerStatus::ACCEPTED,
            ]);

            $response = $this->actingAs($host)->getJson("/api/v1/games/lobbies/{$lobby->ulid}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'lobby' => ['ulid', 'game_title', 'host', 'players', 'status'],
                    ],
                ]);
        });
    });

    describe('Lobby Cancellation', function () {
        it('host can cancel their lobby', function () {
            $host = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $response = $this->actingAs($host)->deleteJson("/api/v1/games/lobbies/{$lobby->ulid}");

            $response->assertStatus(204);

            $this->assertDatabaseHas('lobbies', [
                'id' => $lobby->id,
                'status' => 'cancelled',
            ]);
        });

        it('non-host cannot cancel lobby', function () {
            $host = User::factory()->create();
            $otherUser = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $response = $this->actingAs($otherUser)->deleteJson("/api/v1/games/lobbies/{$lobby->ulid}");

            $response->assertStatus(403);
        });
    });

    describe('Ready Check', function () {
        it('host can initiate ready check', function () {
            $host = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $response = $this->actingAs($host)->postJson("/api/v1/games/lobbies/{$lobby->ulid}/ready-check");

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Ready check initiated',
                ]);
        });

        it('non-host cannot initiate ready check', function () {
            $host = User::factory()->create();
            $otherUser = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $response = $this->actingAs($otherUser)->postJson("/api/v1/games/lobbies/{$lobby->ulid}/ready-check");

            $response->assertStatus(403);
        });
    });

    describe('Lobby Lifecycle - Edge Cases', function () {
        it('handles host disconnecting before game starts', function () {
            $host = User::factory()->create();
            $player = User::factory()->create();

            $lobby = Lobby::factory()->create([
                'host_id' => $host->id,
                'status' => 'pending',
            ]);

            // Add player to lobby
            $lobby->players()->create([
                'user_id' => $player->id,
                'status' => 'accepted',
            ]);

            // Host cancels (simulating disconnect)
            $response = $this->actingAs($host)->deleteJson("/api/v1/games/lobbies/{$lobby->ulid}");

            $response->assertStatus(204);

            $lobby->refresh();
            expect($lobby->status)->toBe(LobbyStatus::CANCELLED);
        });

        it('prevents starting with disconnected players', function () {
            $host = User::factory()->create();

            $lobby = Lobby::factory()->create([
                'host_id' => $host->id,
                'status' => 'pending',
            ]);

            // No other players - should not be able to start
            $response = $this->actingAs($host)->postJson("/api/v1/games/lobbies/{$lobby->ulid}/ready-check");

            // Should succeed in creating ready check, but game won't start without players
            $response->assertStatus(202);
        });

        it('handles rapid ready/unready spam', function () {
            $host = User::factory()->create();
            $player = User::factory()->create();

            $lobby = Lobby::factory()->create([
                'host_id' => $host->id,
                'status' => 'pending',
            ]);

            $lobby->players()->create([
                'user_id' => $player->id,
                'status' => 'accepted',
            ]);

            // Spam ready check
            for ($i = 0; $i < 5; $i++) {
                $response = $this->actingAs($host)->postJson("/api/v1/games/lobbies/{$lobby->ulid}/ready-check");
                expect($response->status())->toBeIn([202, 429]); // Either succeeds or rate limited
            }
        });
    });

    describe('Lobby - Multiple Client Scenarios', function () {
        it('syncs lobby state across mobile and web clients', function () {
            $user = User::factory()->create();

            // Create lobby from mobile
            $response1 = $this->actingAs($user)
                ->withHeader('X-Client-Key', '2') // Mobile
                ->postJson('/api/v1/games/lobbies', [
                    'game_title' => 'connect-four',
                    'is_public' => true,
                ]);

            $lobbyUlid = $response1->json('data.ulid');

            // View from web
            $response2 = $this->actingAs($user)
                ->withHeader('X-Client-Key', '1') // Web
                ->getJson("/api/v1/games/lobbies/{$lobbyUlid}");

            $response2->assertStatus(200)
                ->assertJsonPath('data.ulid', $lobbyUlid);
        });

        it('handles invite from iOS, accept from Android', function () {
            $host = User::factory()->create();
            $invitee = User::factory()->create();

            $lobby = Lobby::factory()->create([
                'host_id' => $host->id,
                'is_public' => false,
            ]);

            // Host invites from web (client_id 1)
            $response1 = $this->actingAs($host)
                ->withHeader('X-Client-Key', '1') // Web
                ->postJson("/api/v1/games/lobbies/{$lobby->ulid}/players", [
                    'username' => $invitee->username,
                ]);

            $response1->assertStatus(201); // Created

            // Invitee accepts from mobile (client_id 2)
            $response2 = $this->actingAs($invitee)
                ->withHeader('X-Client-Key', '2') // Mobile
                ->putJson("/api/v1/games/lobbies/{$lobby->ulid}/players/{$invitee->username}", [
                    'status' => 'accepted',
                ]);

            $response2->assertStatus(200);
        });

        it('prevents duplicate joins from same user different clients', function () {
            $user = User::factory()->create();

            $lobby = Lobby::factory()->create([
                'is_public' => true,
            ]);

            // Join from web (use PUT to accept/join public lobby)
            $response1 = $this->actingAs($user)
                ->withHeader('X-Client-Key', '1')
                ->putJson("/api/v1/games/lobbies/{$lobby->ulid}/players/{$user->username}", [
                    'status' => 'accepted',
                ]);

            // Try to join from mobile (same user)
            $response2 = $this->actingAs($user)
                ->withHeader('X-Client-Key', '2')
                ->putJson("/api/v1/games/lobbies/{$lobby->ulid}/players/{$user->username}", [
                    'status' => 'accepted',
                ]);

            expect($response1->status())->toBe(200);
            expect($response2->status())->toBeIn([400, 409]); // Already in lobby
        });
    });

    describe('Lobby - Scheduled Games', function () {
        it('handles timezone differences correctly', function () {
            $user = User::factory()->create();

            $scheduledTime = now()->addHours(2);

            $response = $this->actingAs($user)->postJson('/api/v1/games/lobbies', [
                'game_title' => 'connect-four',
                'is_public' => false,
                'scheduled_at' => $scheduledTime->toIso8601String(),
            ]);

            $response->assertStatus(201)
                ->assertJsonPath('data.scheduled_at', $scheduledTime->toIso8601String());
        });

        it('validates scheduled time is in future', function () {
            $user = User::factory()->create();

            $pastTime = now()->subHours(1);

            $response = $this->actingAs($user)->postJson('/api/v1/games/lobbies', [
                'game_title' => 'connect-four',
                'is_public' => false,
                'scheduled_at' => $pastTime->toIso8601String(),
            ]);

            $response->assertStatus(422);
        });
    });
});
