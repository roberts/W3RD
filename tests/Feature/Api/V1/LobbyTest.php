<?php

use App\Enums\LobbyPlayerStatus;
use App\Models\Auth\User;
use App\Models\Game\Lobby;
use App\Models\Game\LobbyPlayer;

describe('Lobby Management', function () {
    describe('Lobby Creation', function () {
        it('authenticated user can create a private lobby', function () {
            $host = User::factory()->create();

            $response = $this->actingAs($host)->postJson('/api/v1/games/lobbies', [
                'game_title' => 'validate-four',
                'is_public' => false,
                'min_players' => 2,
            ]);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'data' => ['ulid', 'game_title', 'is_public', 'min_players'],
                ]);

            $this->assertDatabaseHas('lobbies', [
                'game_title' => 'validate-four',
                'host_id' => $host->id,
                'is_public' => false,
                'status' => 'pending',
            ]);
        });

        it('authenticated user can create a public lobby', function () {
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

        it('user can create lobby with invitees', function () {
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

        it('user can create scheduled lobby', function () {
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
});
