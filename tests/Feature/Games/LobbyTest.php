<?php

use App\Enums\LobbyPlayerStatus;
use App\Models\Auth\User;
use App\Models\Game\Lobby;
use App\Models\Game\LobbyPlayer;

describe('Lobby Management', function () {
    describe('Lobby Creation', function () {
        test('authenticated user can create a private lobby', function () {
            $host = User::factory()->create();

            $response = $this->actingAs($host)->postJson('/api/v1/games/lobbies', [
                'game_title' => 'validate-four',
                'is_public' => false,
                'min_players' => 2,
            ]);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'lobby' => ['ulid', 'game_title', 'is_public', 'min_players'],
                ]);

            $this->assertDatabaseHas('lobbies', [
                'game_title' => 'validate-four',
                'host_id' => $host->id,
                'is_public' => false,
                'status' => 'pending',
            ]);
        });

        test('authenticated user can create a public lobby', function () {
            $host = User::factory()->create();

            $response = $this->actingAs($host)->postJson('/api/v1/games/lobbies', [
                'game_title' => 'validate-four',
                'is_public' => true,
                'min_players' => 4,
            ]);

            $response->assertStatus(201);

            $this->assertDatabaseHas('lobbies', [
                'host_id' => $host->id,
                'is_public' => true,
            ]);
        });

        test('user can create lobby with invitees', function () {
            $host = User::factory()->create();
            $invitee1 = User::factory()->create();
            $invitee2 = User::factory()->create();

            $response = $this->actingAs($host)->postJson('/api/v1/games/lobbies', [
                'game_title' => 'validate-four',
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

        test('user can create scheduled lobby', function () {
            $host = User::factory()->create();
            $scheduledTime = now()->addHours(2)->toIso8601String();

            $response = $this->actingAs($host)->postJson('/api/v1/games/lobbies', [
                'game_title' => 'validate-four',
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
        test('can list all public lobbies', function () {
            $host = User::factory()->create();

            // Create public and private lobbies
            Lobby::factory()->public()->create();
            Lobby::factory()->public()->create();
            Lobby::factory()->private()->create();

            $response = $this->actingAs($host)->getJson('/api/v1/games/lobbies');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'lobbies' => [
                        '*' => ['ulid', 'game_title', 'host', 'min_players', 'current_players', 'status'],
                    ],
                ]);

            // Should only return public lobbies
            expect($response->json('lobbies'))->toHaveCount(2);
        });

        test('can view lobby details', function () {
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
                    'lobby' => ['ulid', 'game_title', 'host', 'players', 'status'],
                ]);
        });
    });

    describe('Lobby Cancellation', function () {
        test('host can cancel their lobby', function () {
            $host = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $response = $this->actingAs($host)->deleteJson("/api/v1/games/lobbies/{$lobby->ulid}");

            $response->assertStatus(204);

            $this->assertDatabaseHas('lobbies', [
                'id' => $lobby->id,
                'status' => 'cancelled',
            ]);
        });

        test('non-host cannot cancel lobby', function () {
            $host = User::factory()->create();
            $otherUser = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $response = $this->actingAs($otherUser)->deleteJson("/api/v1/games/lobbies/{$lobby->ulid}");

            $response->assertStatus(403);
        });
    });

    describe('Ready Check', function () {
        test('host can initiate ready check', function () {
            $host = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $response = $this->actingAs($host)->postJson("/api/v1/games/lobbies/{$lobby->ulid}/ready-check");

            $response->assertStatus(202)
                ->assertJson([
                    'message' => 'Ready check initiated',
                ]);
        });

        test('non-host cannot initiate ready check', function () {
            $host = User::factory()->create();
            $otherUser = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $response = $this->actingAs($otherUser)->postJson("/api/v1/games/lobbies/{$lobby->ulid}/ready-check");

            $response->assertStatus(403);
        });
    });

    describe('Player Invitations', function () {
        test('host can invite player to lobby', function () {
            $host = User::factory()->create();
            $invitee = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $response = $this->actingAs($host)->postJson("/api/v1/games/lobbies/{$lobby->ulid}/players", [
                'username' => $invitee->username,
            ]);

            $response->assertStatus(201);

            $this->assertDatabaseHas('lobby_players', [
                'lobby_id' => $lobby->id,
                'user_id' => $invitee->id,
                'status' => 'pending',
            ]);
        });

        test('non-host cannot invite players', function () {
            $host = User::factory()->create();
            $otherUser = User::factory()->create();
            $invitee = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $response = $this->actingAs($otherUser)->postJson("/api/v1/games/lobbies/{$lobby->ulid}/players", [
                'username' => $invitee->username,
            ]);

            $response->assertStatus(403);
        });
    });

    describe('Player Responses', function () {
        test('invitee can accept invitation', function () {
            $host = User::factory()->create();
            $invitee = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id, 'min_players' => 3]);

            $lobbyPlayer = LobbyPlayer::factory()->create([
                'lobby_id' => $lobby->id,
                'user_id' => $invitee->id,
                'status' => LobbyPlayerStatus::PENDING,
            ]);

            $response = $this->actingAs($invitee)->putJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$invitee->id}",
                ['status' => 'accepted']
            );

            $response->assertStatus(200);

            $this->assertDatabaseHas('lobby_players', [
                'id' => $lobbyPlayer->id,
                'status' => 'accepted',
            ]);
        });

        test('invitee can decline invitation', function () {
            $host = User::factory()->create();
            $invitee = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $lobbyPlayer = LobbyPlayer::factory()->create([
                'lobby_id' => $lobby->id,
                'user_id' => $invitee->id,
                'status' => LobbyPlayerStatus::PENDING,
            ]);

            $response = $this->actingAs($invitee)->putJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$invitee->id}",
                ['status' => 'declined']
            );

            $response->assertStatus(200);

            $this->assertDatabaseHas('lobby_players', [
                'id' => $lobbyPlayer->id,
                'status' => 'declined',
            ]);
        });

        test('user can join public lobby', function () {
            $host = User::factory()->create();
            $joiner = User::factory()->create();
            $lobby = Lobby::factory()->public()->create(['host_id' => $host->id, 'min_players' => 3]);

            $response = $this->actingAs($joiner)->putJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$joiner->id}",
                ['status' => 'accepted']
            );

            $response->assertStatus(200);

            $this->assertDatabaseHas('lobby_players', [
                'lobby_id' => $lobby->id,
                'user_id' => $joiner->id,
                'status' => 'accepted',
            ]);
        });
    });

    describe('Player Management', function () {
        test('host can kick player from lobby', function () {
            $host = User::factory()->create();
            $player = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $lobbyPlayer = LobbyPlayer::factory()->create([
                'lobby_id' => $lobby->id,
                'user_id' => $player->id,
            ]);

            $response = $this->actingAs($host)->deleteJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$player->id}"
            );

            $response->assertStatus(204);

            $this->assertDatabaseMissing('lobby_players', [
                'id' => $lobbyPlayer->id,
            ]);
        });

        test('host cannot kick themselves', function () {
            $host = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $response = $this->actingAs($host)->deleteJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$host->id}"
            );

            $response->assertStatus(400);
        });

        test('non-host cannot kick players', function () {
            $host = User::factory()->create();
            $otherUser = User::factory()->create();
            $player = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            LobbyPlayer::factory()->create([
                'lobby_id' => $lobby->id,
                'user_id' => $player->id,
            ]);

            $response = $this->actingAs($otherUser)->deleteJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$player->id}"
            );

            $response->assertStatus(403);
        });
    });
});
