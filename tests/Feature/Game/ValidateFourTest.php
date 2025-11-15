<?php

use App\Models\Game\Game;
use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user1 = User::factory()->create();
    $this->user2 = User::factory()->create();
});

test('authenticated user can get game rules for a specific game title', function () {
    $response = $this->actingAs($this->user1)
        ->getJson('/api/v1/games/validate_four/rules');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'game' => [
                'title',
                'description',
                'min_players',
                'max_players',
            ],
            'board',
            'available_actions',
            'win_conditions',
            'draw_conditions',
        ]);
});

test('unauthenticated user can get game rules', function () {
    $response = $this->getJson('/api/v1/games/validate_four/rules');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'game',
            'board',
            'available_actions',
        ]);
});

test('game rules include mode-specific rules for pop out mode', function () {
    $response = $this->getJson('/api/v1/games/validate_four/rules?mode=pop_out');

    $response->assertStatus(200)
        ->assertJsonPath('action_rules.pop_out.description', 'Remove your own disc from the bottom of a column, causing all discs above to fall down');
});

test('authenticated user can drop a disc in their game', function () {
    // Create a game with two players
    $game = Game::factory()->create([
        'game_title' => 'validate_four',
        'game_mode' => 'standard',
        'game_status' => 'active',
        'game_state' => [
            'board' => array_fill(0, 6, array_fill(0, 7, null)),
            'player_one_ulid' => 'player1ulid',
            'player_two_ulid' => 'player2ulid',
            'current_player_ulid' => 'player1ulid',
            'columns' => 7,
            'rows' => 6,
            'connect_count' => 4,
        ],
    ]);

    $player1 = Player::factory()->create([
        'game_id' => $game->id,
        'user_id' => $this->user1->id,
        'ulid' => 'player1ulid',
    ]);

    $player2 = Player::factory()->create([
        'game_id' => $game->id,
        'user_id' => $this->user2->id,
        'ulid' => 'player2ulid',
    ]);

    $response = $this->actingAs($this->user1)
        ->postJson("/api/v1/games/${game->ulid}/action", [
            'action_type' => 'drop_disc',
            'action_details' => ['column' => 3],
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'game_state',
            'game_status',
            'winner',
        ]);

    // Verify the game state was updated
    $game->refresh();
    expect($game->game_state['board'][5][3])->toBe('player1ulid');
});

test('user cannot take action on another players turn', function () {
    $game = Game::factory()->create([
        'game_title' => 'validate_four',
        'game_mode' => 'standard',
        'game_status' => 'active',
        'game_state' => [
            'board' => array_fill(0, 6, array_fill(0, 7, null)),
            'player_one_ulid' => 'player1ulid',
            'player_two_ulid' => 'player2ulid',
            'current_player_ulid' => 'player1ulid',
            'columns' => 7,
            'rows' => 6,
            'connect_count' => 4,
        ],
    ]);

    $player1 = Player::factory()->create([
        'game_id' => $game->id,
        'user_id' => $this->user1->id,
        'ulid' => 'player1ulid',
    ]);

    $player2 = Player::factory()->create([
        'game_id' => $game->id,
        'user_id' => $this->user2->id,
        'ulid' => 'player2ulid',
    ]);

    // User 2 tries to take action on User 1's turn
    $response = $this->actingAs($this->user2)
        ->postJson("/api/v1/games/${game->ulid}/action", [
            'action_type' => 'drop_disc',
            'action_details' => ['column' => 3],
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Invalid turn',
            'message' => 'It is not your turn.',
        ]);
});

test('user cannot drop disc in full column', function () {
    // Create a board with column 3 full
    $board = array_fill(0, 6, array_fill(0, 7, null));
    for ($row = 0; $row < 6; $row++) {
        $board[$row][3] = $row % 2 === 0 ? 'player1ulid' : 'player2ulid';
    }

    $game = Game::factory()->create([
        'game_title' => 'validate_four',
        'game_mode' => 'standard',
        'game_status' => 'active',
        'game_state' => [
            'board' => $board,
            'player_one_ulid' => 'player1ulid',
            'player_two_ulid' => 'player2ulid',
            'current_player_ulid' => 'player1ulid',
            'columns' => 7,
            'rows' => 6,
            'connect_count' => 4,
        ],
    ]);

    $player1 = Player::factory()->create([
        'game_id' => $game->id,
        'user_id' => $this->user1->id,
        'ulid' => 'player1ulid',
    ]);

    $response = $this->actingAs($this->user1)
        ->postJson("/api/v1/games/${game->ulid}/action", [
            'action_type' => 'drop_disc',
            'action_details' => ['column' => 3],
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Invalid move',
        ]);
});

test('game detects horizontal win condition', function () {
    // Create a board with 3 consecutive discs for player 1
    $board = array_fill(0, 6, array_fill(0, 7, null));
    $board[5][0] = 'player1ulid';
    $board[5][1] = 'player1ulid';
    $board[5][2] = 'player1ulid';

    $game = Game::factory()->create([
        'game_title' => 'validate_four',
        'game_mode' => 'standard',
        'game_status' => 'active',
        'game_state' => [
            'board' => $board,
            'player_one_ulid' => 'player1ulid',
            'player_two_ulid' => 'player2ulid',
            'current_player_ulid' => 'player1ulid',
            'columns' => 7,
            'rows' => 6,
            'connect_count' => 4,
        ],
    ]);

    $player1 = Player::factory()->create([
        'game_id' => $game->id,
        'user_id' => $this->user1->id,
        'ulid' => 'player1ulid',
    ]);

    // Drop the 4th disc to complete the horizontal line
    $response = $this->actingAs($this->user1)
        ->postJson("/api/v1/games/${game->ulid}/action", [
            'action_type' => 'drop_disc',
            'action_details' => ['column' => 3],
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('game_status', 'completed')
        ->assertJsonPath('winner.ulid', 'player1ulid');

    // Verify game was marked as completed
    $game->refresh();
    expect($game->game_status)->toBe('completed');
});

test('pop out mode allows popping own disc from bottom', function () {
    // Create a board with some discs
    $board = array_fill(0, 6, array_fill(0, 7, null));
    $board[5][3] = 'player1ulid';
    $board[4][3] = 'player2ulid';
    $board[3][3] = 'player1ulid';

    $game = Game::factory()->create([
        'game_title' => 'validate_four',
        'game_mode' => 'pop_out',
        'game_status' => 'active',
        'game_state' => [
            'board' => $board,
            'player_one_ulid' => 'player1ulid',
            'player_two_ulid' => 'player2ulid',
            'current_player_ulid' => 'player1ulid',
            'columns' => 7,
            'rows' => 6,
            'connect_count' => 4,
        ],
    ]);

    $player1 = Player::factory()->create([
        'game_id' => $game->id,
        'user_id' => $this->user1->id,
        'ulid' => 'player1ulid',
    ]);

    $response = $this->actingAs($this->user1)
        ->postJson("/api/v1/games/${game->ulid}/action", [
            'action_type' => 'pop_out',
            'action_details' => ['column' => 3],
        ]);

    $response->assertStatus(200);

    // Verify the discs shifted down correctly
    $game->refresh();
    expect($game->game_state['board'][5][3])->toBe('player2ulid')
        ->and($game->game_state['board'][4][3])->toBe('player1ulid')
        ->and($game->game_state['board'][3][3])->toBeNull();
});

test('pop out mode prevents popping opponents disc', function () {
    $board = array_fill(0, 6, array_fill(0, 7, null));
    $board[5][3] = 'player2ulid'; // Opponent's disc at bottom

    $game = Game::factory()->create([
        'game_title' => 'validate_four',
        'game_mode' => 'pop_out',
        'game_status' => 'active',
        'game_state' => [
            'board' => $board,
            'player_one_ulid' => 'player1ulid',
            'player_two_ulid' => 'player2ulid',
            'current_player_ulid' => 'player1ulid',
            'columns' => 7,
            'rows' => 6,
            'connect_count' => 4,
        ],
    ]);

    $player1 = Player::factory()->create([
        'game_id' => $game->id,
        'user_id' => $this->user1->id,
        'ulid' => 'player1ulid',
    ]);

    $response = $this->actingAs($this->user1)
        ->postJson("/api/v1/games/${game->ulid}/action", [
            'action_type' => 'pop_out',
            'action_details' => ['column' => 3],
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'error' => 'Invalid action',
        ]);
});
