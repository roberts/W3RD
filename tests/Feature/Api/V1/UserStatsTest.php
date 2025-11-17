<?php

use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Player;
use App\Models\Gamification\Point;

describe('User Statistics', function () {
    describe('Stats Display', function () {
        it('shows stats for user with no games', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->getJson('/api/v1/me/stats');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'total_games' => 0,
                        'wins' => 0,
                        'losses' => 0,
                        'win_rate' => 0,
                        'total_points' => 0,
                        'global_rank' => null,
                    ],
                ]);
        });

        it('shows stats for user with games', function () {
            $user = User::factory()->create();

            // Create 3 games: 2 wins, 1 loss
            // Win game 1
            $game1 = Game::factory()->completed()->create();
            $player1 = Player::factory()->create([
                'user_id' => $user->id,
                'game_id' => $game1->id,
            ]);
            $game1->update(['winner_id' => $player1->id]);
            Point::factory()->create([
                'user_id' => $user->id,
                'source_type' => Game::class,
                'source_id' => $game1->id,
                'change' => 100,
            ]);

            // Win game 2
            $game2 = Game::factory()->completed()->create();
            $player2 = Player::factory()->create([
                'user_id' => $user->id,
                'game_id' => $game2->id,
            ]);
            $game2->update(['winner_id' => $player2->id]);
            Point::factory()->create([
                'user_id' => $user->id,
                'source_type' => Game::class,
                'source_id' => $game2->id,
                'change' => 150,
            ]);

            // Lose game 3
            $game3 = Game::factory()->completed()->create();
            $player3 = Player::factory()->create([
                'user_id' => $user->id,
                'game_id' => $game3->id,
            ]);
            // Don't set winner_id to player3, meaning they lost

            $response = $this->actingAs($user)->getJson('/api/v1/me/stats');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'total_games' => 3,
                        'wins' => 2,
                        'losses' => 1,
                        'win_rate' => 66.67,
                        'total_points' => 250,
                        'global_rank' => null,
                    ],
                ]);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/me/stats');

            $response->assertStatus(401);
        });
    });

    describe('Win Rate Calculation', function () {
        it('calculates 100% win rate correctly', function () {
            $user = User::factory()->create();

            // 3 wins, 0 losses
            for ($i = 0; $i < 3; $i++) {
                $game = Game::factory()->completed()->create();
                $player = Player::factory()->create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                ]);
                $game->update(['winner_id' => $player->id]);
            }

            $response = $this->actingAs($user)->getJson('/api/v1/me/stats');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'total_games' => 3,
                        'wins' => 3,
                        'losses' => 0,
                        'win_rate' => 100.0,
                    ],
                ]);
        });

        it('calculates 0% win rate correctly', function () {
            $user = User::factory()->create();

            // 0 wins, 3 losses
            for ($i = 0; $i < 3; $i++) {
                $game = Game::factory()->completed()->create();
                Player::factory()->create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                ]);
                // Don't set winner_id, meaning they lost
            }

            $response = $this->actingAs($user)->getJson('/api/v1/me/stats');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'total_games' => 3,
                        'wins' => 0,
                        'losses' => 3,
                        'win_rate' => 0.0,
                    ],
                ]);
        });

        it('calculates 50% win rate correctly', function () {
            $user = User::factory()->create();

            // 2 wins
            for ($i = 0; $i < 2; $i++) {
                $game = Game::factory()->completed()->create();
                $player = Player::factory()->create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                ]);
                $game->update(['winner_id' => $player->id]);
            }

            // 2 losses
            for ($i = 0; $i < 2; $i++) {
                $game = Game::factory()->completed()->create();
                Player::factory()->create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                ]);
                // Don't set winner_id, meaning they lost
            }

            $response = $this->actingAs($user)->getJson('/api/v1/me/stats');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'total_games' => 4,
                        'wins' => 2,
                        'losses' => 2,
                        'win_rate' => 50.0,
                    ],
                ]);
        });
    });

    describe('Points Aggregation', function () {
        it('sums points correctly across multiple games', function () {
            $user = User::factory()->create();

            // Game 1: 100 points
            $game1 = Game::factory()->completed()->create();
            Player::factory()->create([
                'user_id' => $user->id,
                'game_id' => $game1->id,
            ]);
            Point::factory()->create([
                'user_id' => $user->id,
                'source_type' => Game::class,
                'source_id' => $game1->id,
                'change' => 100,
            ]);

            // Game 2: 250 points
            $game2 = Game::factory()->completed()->create();
            Player::factory()->create([
                'user_id' => $user->id,
                'game_id' => $game2->id,
            ]);
            Point::factory()->create([
                'user_id' => $user->id,
                'source_type' => Game::class,
                'source_id' => $game2->id,
                'change' => 250,
            ]);

            // Game 3: 50 points
            $game3 = Game::factory()->completed()->create();
            Player::factory()->create([
                'user_id' => $user->id,
                'game_id' => $game3->id,
            ]);
            Point::factory()->create([
                'user_id' => $user->id,
                'source_type' => Game::class,
                'source_id' => $game3->id,
                'change' => 50,
            ]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/stats');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'total_points' => 400,
                    ],
                ]);
        });
    });
});
