<?php

use App\Enums\GameTitle;
use App\Models\Auth\User;
use App\Models\Gamification\UserTitleLevel;

describe('User Levels & Experience', function () {
    describe('Levels Display', function () {
        it('shows empty levels for new user', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->getJson('/api/v1/me/levels');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [],
                ]);
        });

        it('shows levels for single game', function () {
            $user = User::factory()->create();

            UserTitleLevel::factory()->create([
                'user_id' => $user->id,
                'title_slug' => GameTitle::CONNECT_FOUR->value,
                'level' => 5,
                'xp_current' => 250,
                'last_played_at' => now(),
            ]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/levels');

            $response->assertStatus(200)
                ->assertJsonCount(1, 'data')
                ->assertJson([
                    'data' => [
                        [
                            'game_title' => GameTitle::CONNECT_FOUR->value,
                            'level' => 5,
                            'experience_points' => 250,
                        ],
                    ],
                ]);

            expect($response->json('data.0.last_played_at'))->not->toBeNull();
        });

        it('shows levels for multiple games', function () {
            $user = User::factory()->create();

            UserTitleLevel::factory()->create([
                'user_id' => $user->id,
                'title_slug' => GameTitle::CONNECT_FOUR,
                'level' => 5,
                'xp_current' => 250,
            ]);

            UserTitleLevel::factory()->create([
                'user_id' => $user->id,
                'title_slug' => GameTitle::HEARTS,
                'level' => 3,
                'xp_current' => 100,
            ]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/levels');

            $response->assertStatus(200)
                ->assertJsonCount(2, 'data');

            $titles = collect($response->json('data'))->pluck('game_title')->toArray();
            expect($titles)->toContain('connect-four');
            expect($titles)->toContain('hearts');
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/me/levels');

            $response->assertStatus(401);
        });

        it('only shows authenticated user levels', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            UserTitleLevel::factory()->create([
                'user_id' => $user1->id,
                'title_slug' => GameTitle::CONNECT_FOUR,
                'level' => 10,
            ]);

            UserTitleLevel::factory()->create([
                'user_id' => $user2->id,
                'title_slug' => GameTitle::HEARTS,
                'level' => 15,
            ]);

            $response = $this->actingAs($user1)->getJson('/api/v1/me/levels');

            $response->assertStatus(200)
                ->assertJsonCount(1, 'data')
                ->assertJson([
                    'data' => [
                        [
                            'game_title' => 'connect-four',
                            'level' => 10,
                        ],
                    ],
                ]);
        });
    });

    describe('Experience Points', function () {
        it('tracks experience points correctly', function () {
            $user = User::factory()->create();

            UserTitleLevel::factory()->create([
                'user_id' => $user->id,
                'title_slug' => GameTitle::CONNECT_FOUR,
                'level' => 1,
                'xp_current' => 0,
            ]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/levels');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        [
                            'experience_points' => 0,
                        ],
                    ],
                ]);
        });

        it('shows increasing experience points', function () {
            $user = User::factory()->create();

            UserTitleLevel::factory()->create([
                'user_id' => $user->id,
                'title_slug' => GameTitle::CONNECT_FOUR,
                'level' => 5,
                'xp_current' => 1500,
            ]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/levels');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        [
                            'level' => 5,
                            'experience_points' => 1500,
                        ],
                    ],
                ]);
        });
    });

    describe('Last Played Timestamp', function () {
        it('updates last_played_at timestamp', function () {
            $user = User::factory()->create();

            $lastPlayed = now()->subDays(3);

            UserTitleLevel::factory()->create([
                'user_id' => $user->id,
                'title_slug' => GameTitle::CONNECT_FOUR,
                'last_played_at' => $lastPlayed,
            ]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/levels');

            $response->assertStatus(200);

            $responseLastPlayed = $response->json('data.0.last_played_at');
            expect($responseLastPlayed)->not->toBeNull();
        });

        it('sorts by last_played_at descending', function () {
            $user = User::factory()->create();

            UserTitleLevel::factory()->create([
                'user_id' => $user->id,
                'title_slug' => GameTitle::CONNECT_FOUR,
                'last_played_at' => now()->subDays(5),
            ]);

            UserTitleLevel::factory()->create([
                'user_id' => $user->id,
                'title_slug' => GameTitle::HEARTS,
                'last_played_at' => now()->subDays(1),
            ]);

            UserTitleLevel::factory()->create([
                'user_id' => $user->id,
                'title_slug' => GameTitle::SPADES,
                'last_played_at' => now()->subDays(3),
            ]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/levels');

            $response->assertStatus(200)
                ->assertJsonCount(3, 'data');

            // First should be most recent (hearts)
            expect($response->json('data.0.game_title'))->toBe('hearts');
        });
    });
});
