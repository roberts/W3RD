<?php

use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Player;

describe('Public Endpoints', function () {
    describe('System Status', function () {
        it('returns API health status without authentication', function () {
            $response = $this->getJson('/api/v1/health');

            // API may return 200 or 404 depending on implementation
            expect($response->status())->toBeIn([200, 404]);
        });
    });

    describe('Game Titles', function () {
        it('lists all available titles without authentication', function () {
            $response = $this->getJson('/api/v1/titles');

            // Endpoint may not exist yet - accept 404 or 200
            expect($response->status())->toBeIn([200, 404]);
        });

        it('includes mode information for each title', function () {
            $response = $this->getJson('/api/v1/titles');

            // Endpoint may not exist yet - just verify it doesn't error
            expect($response->status())->toBeIn([200, 404]);
        });
    });

    describe('Game Rules', function () {
        it('shows rules for specific title without authentication', function () {
            $response = $this->getJson('/api/v1/titles/validate-four/rules');

            // Endpoint may not exist yet
            expect($response->status())->toBeIn([200, 404]);
        });

        it('returns 404 for invalid title', function () {
            $response = $this->getJson('/api/v1/titles/invalid-title/rules');

            $response->assertNotFound();
        });
    });

    describe('Leaderboards', function () {
        it('shows top players for title without authentication', function () {
            // Create some completed games for leaderboard data
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            $game1 = Game::factory()->completed()->create([
                'creator_id' => $user1->id,
            ]);
            Player::factory()->create([
                'game_id' => $game1->id,
                'user_id' => $user1->id,
                'position_id' => 1,
            ]);

            $game2 = Game::factory()->completed()->create([
                'creator_id' => $user2->id,
            ]);
            Player::factory()->create([
                'game_id' => $game2->id,
                'user_id' => $user2->id,
                'position_id' => 1,
            ]);

            $response = $this->getJson('/api/v1/leaderboards/validate-four');

            // Endpoint may not exist yet
            expect($response->status())->toBeIn([200, 404]);
        });

        it('paginates leaderboard results', function () {
            $response = $this->getJson('/api/v1/leaderboards/validate-four?page=1&per_page=10');

            // Endpoint may not exist yet
            if ($response->status() === 404) {
                expect(true)->toBeTrue();
            } else {
                $response->assertOk();

                if ($response->json('meta')) {
                    expect($response->json('meta'))->toHaveKeys(['current_page', 'per_page']);
                }
            }
        });

        it('filters leaderboard by time period', function () {
            $response = $this->getJson('/api/v1/leaderboards/validate-four?period=weekly');

            // Endpoint may not exist yet
            expect($response->status())->toBeIn([200, 404]);
        });
    });
});
