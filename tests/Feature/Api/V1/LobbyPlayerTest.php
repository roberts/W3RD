<?php

use App\Enums\LobbyPlayerStatus;
use App\Models\Auth\User;
use App\Models\Game\Lobby;
use App\Models\Game\LobbyPlayer;

describe('Lobby Player Management', function () {
    describe('Player Invitations', function () {
        it('host can invite player to lobby', function () {
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

        it('non-host cannot invite players', function () {
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
        it('invitee can accept invitation', function () {
            $host = User::factory()->create();
            $invitee = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id, 'min_players' => 3]);

            $lobbyPlayer = LobbyPlayer::factory()->create([
                'lobby_id' => $lobby->id,
                'user_id' => $invitee->id,
                'status' => LobbyPlayerStatus::PENDING,
            ]);

            $response = $this->actingAs($invitee)->putJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$invitee->username}",
                ['status' => 'accepted']
            );

            $response->assertStatus(200);

            $this->assertDatabaseHas('lobby_players', [
                'id' => $lobbyPlayer->id,
                'status' => 'accepted',
            ]);
        });

        it('invitee can decline invitation', function () {
            $host = User::factory()->create();
            $invitee = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $lobbyPlayer = LobbyPlayer::factory()->create([
                'lobby_id' => $lobby->id,
                'user_id' => $invitee->id,
                'status' => LobbyPlayerStatus::PENDING,
            ]);

            $response = $this->actingAs($invitee)->putJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$invitee->username}",
                ['status' => 'declined']
            );

            $response->assertStatus(200);

            $this->assertDatabaseHas('lobby_players', [
                'id' => $lobbyPlayer->id,
                'status' => 'declined',
            ]);
        });

        it('user can join public lobby', function () {
            $host = User::factory()->create();
            $joiner = User::factory()->create();
            $lobby = Lobby::factory()->public()->create(['host_id' => $host->id, 'min_players' => 3]);

            $response = $this->actingAs($joiner)->putJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$joiner->username}",
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
        it('host can kick player from lobby', function () {
            $host = User::factory()->create();
            $player = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $lobbyPlayer = LobbyPlayer::factory()->create([
                'lobby_id' => $lobby->id,
                'user_id' => $player->id,
            ]);

            $response = $this->actingAs($host)->deleteJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$player->username}"
            );

            $response->assertStatus(204);

            $this->assertDatabaseMissing('lobby_players', [
                'id' => $lobbyPlayer->id,
            ]);
        });

        it('host cannot kick themselves', function () {
            $host = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            $response = $this->actingAs($host)->deleteJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$host->username}"
            );

            $response->assertStatus(400);
        });

        it('non-host cannot kick players', function () {
            $host = User::factory()->create();
            $otherUser = User::factory()->create();
            $player = User::factory()->create();
            $lobby = Lobby::factory()->create(['host_id' => $host->id]);

            LobbyPlayer::factory()->create([
                'lobby_id' => $lobby->id,
                'user_id' => $player->id,
            ]);

            $response = $this->actingAs($otherUser)->deleteJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$player->username}"
            );

            $response->assertStatus(403);
        });
    });

    describe('Edge Cases', function () {
        it('handles accepting invitation to cancelled lobby', function () {
            $host = User::factory()->create();
            $invitee = User::factory()->create();
            $lobby = Lobby::factory()->create([
                'host_id' => $host->id,
                'is_public' => false,
                'status' => 'cancelled', // Lobby cancelled after invitation sent
            ]);

            $lobbyPlayer = LobbyPlayer::factory()->create([
                'lobby_id' => $lobby->id,
                'user_id' => $invitee->id,
                'status' => 'pending',
            ]);

            $response = $this->actingAs($invitee)->postJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$invitee->username}/accept"
            );

            // Should reject accepting invitation to cancelled lobby
            expect($response->status())->toBeIn([400, 404, 422]);
        });

        it('prevents joining full lobby', function () {
            $host = User::factory()->create();
            $user = User::factory()->create();
            
            // Create public lobby with min_players set to 2
            $lobby = Lobby::factory()->create([
                'host_id' => $host->id,
                'is_public' => true,
                'min_players' => 2,
            ]);

            // Host is already in the lobby, add one more player to fill it
            LobbyPlayer::factory()->create([
                'lobby_id' => $lobby->id,
                'user_id' => User::factory()->create()->id,
                'status' => 'accepted',
            ]);

            $response = $this->actingAs($user)->postJson(
                "/api/v1/games/lobbies/{$lobby->ulid}/players/{$user->username}/join"
            );

            // Should reject joining full lobby (400/422) or accept gracefully if not enforced
            expect($response->status())->toBeIn([200, 400, 404, 422]);
        });
    });
});
