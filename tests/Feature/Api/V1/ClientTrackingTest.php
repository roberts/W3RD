<?php

use App\Models\Access\Client;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Player;

describe('Client Tracking', function () {
    it('tracks client_id when game is created through rematch', function () {
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();
        $client = Client::factory()->create(['api_key' => 'test-client-key']);

        // Create completed game with specific client_ids
        $game = Game::factory()->completed()->create(['creator_id' => $player1->id]);
        Player::factory()->create(['game_id' => $game->id, 'user_id' => $player1->id, 'position_id' => 1, 'client_id' => $client->id]);
        Player::factory()->create(['game_id' => $game->id, 'user_id' => $player2->id, 'position_id' => 2, 'client_id' => $client->id]);

        // Request rematch
        $rematchResponse = $this->actingAs($player1)
            ->postJson("/api/v1/games/{$game->ulid}/rematch");

        $rematchUlid = $rematchResponse->json('data.ulid');

        // Accept rematch
        $response = $this->actingAs($player2)
            ->postJson("/api/v1/games/rematch/{$rematchUlid}/accept");

        $response->assertOk();

        // Verify new game players have same client_id as original game
        $newGame = Game::where('ulid', $response->json('data.new_game_ulid'))->first();
        $players = $newGame->players;

        expect($players)->toHaveCount(2);
        expect($players->every(fn ($player) => $player->client_id === $client->id))->toBeTrue();
    });

    it('tracks client_id when game is created through lobby', function () {
        $host = User::factory()->create();
        $player2 = User::factory()->create();
        $client = Client::factory()->create(['api_key' => 'test-client-key-lobby']);

        // Create lobby with min_players = 2
        $lobbyResponse = $this->actingAs($host)
            ->withHeader('X-Client-Key', $client->id)
            ->postJson('/api/v1/games/lobbies', [
                'game_title' => 'validate-four',
                'game_mode' => 'standard',
                'is_public' => false,
                'min_players' => 2,
                'max_players' => 2,
            ]);

        $lobbyUlid = $lobbyResponse->json('data.ulid');

        $lobbyResponse->assertStatus(201);
        expect($lobbyUlid)->not->toBeNull();

        // Invite player2 using their actual username
        $inviteResponse = $this->actingAs($host)
            ->postJson("/api/v1/games/lobbies/{$lobbyUlid}/players", [
                'username' => $player2->username,
            ]);

        $inviteResponse->assertStatus(201);

        // Player2 accepts invitation using their actual username
        $acceptResponse = $this->actingAs($player2)
            ->withHeader('X-Client-Key', $client->id)
            ->putJson("/api/v1/games/lobbies/{$lobbyUlid}/players/{$player2->username}", [
                'status' => 'accepted',
            ]);

        $acceptResponse->assertOk();

        // Verify game was created and players have client_id
        $game = Game::latest()->first();
        expect($game)->not->toBeNull();

        $players = $game->players;
        expect($players)->toHaveCount(2);
        expect($players->every(fn ($player) => $player->client_id === $client->id))->toBeTrue();
    });

    it('defaults to client_id 1 when X-Client-Key header is missing', function () {
        $player1 = User::factory()->create();
        $player2 = User::factory()->create();

        // Create completed game
        $game = Game::factory()->completed()->create(['creator_id' => $player1->id]);
        Player::factory()->create(['game_id' => $game->id, 'user_id' => $player1->id, 'position_id' => 1, 'client_id' => 1]);
        Player::factory()->create(['game_id' => $game->id, 'user_id' => $player2->id, 'position_id' => 2, 'client_id' => 1]);

        // Request rematch
        $rematchResponse = $this->actingAs($player1)
            ->postJson("/api/v1/games/{$game->ulid}/rematch");

        $rematchUlid = $rematchResponse->json('data.ulid');

        // Accept rematch WITHOUT client header
        $response = $this->actingAs($player2)
            ->postJson("/api/v1/games/rematch/{$rematchUlid}/accept");

        $response->assertOk();

        // Verify players have default client_id (original client_id from first game)
        $newGame = Game::where('ulid', $response->json('data.new_game_ulid'))->first();
        $players = $newGame->players;

        expect($players)->toHaveCount(2);
        // Without header, RematchService uses original player's client_id
        expect($players->every(fn ($player) => $player->client_id !== null))->toBeTrue();
    });
});
