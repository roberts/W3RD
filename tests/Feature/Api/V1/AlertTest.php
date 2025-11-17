<?php

use App\Models\Alert;
use App\Models\Auth\User;

describe('Alert Management', function () {
    describe('Alert Listing', function () {
        it('can list alerts for authenticated user', function () {
            $user = User::factory()->create();

            // Create some alerts for the user
            Alert::factory()->count(3)->create(['user_id' => $user->id]);

            // Create some alerts for other users (should not appear)
            Alert::factory()->count(2)->create();

            $response = $this->actingAs($user)->getJson('/api/v1/me/alerts');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'ulid',
                            'type',
                            'data',
                            'read_at',
                            'created_at',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                ])
                ->assertJsonCount(3, 'data');
        });

        it('orders alerts by created_at descending', function () {
            $user = User::factory()->create();

            $alert1 = Alert::factory()->create([
                'user_id' => $user->id,
                'created_at' => now()->subDays(2),
            ]);

            $alert2 = Alert::factory()->create([
                'user_id' => $user->id,
                'created_at' => now()->subDay(),
            ]);

            $alert3 = Alert::factory()->create([
                'user_id' => $user->id,
                'created_at' => now(),
            ]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/alerts');

            $response->assertStatus(200);

            $data = $response->json('data');

            expect($data[0]['ulid'])->toBe($alert3->ulid);
            expect($data[1]['ulid'])->toBe($alert2->ulid);
            expect($data[2]['ulid'])->toBe($alert1->ulid);
        });

        it('paginates alerts with 20 per page', function () {
            $user = User::factory()->create();

            // Create 25 alerts (more than one page)
            Alert::factory()->count(25)->create(['user_id' => $user->id]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/alerts');

            $response->assertStatus(200)
                ->assertJsonPath('meta.per_page', 20)
                ->assertJsonPath('meta.total', 25)
                ->assertJsonPath('meta.last_page', 2)
                ->assertJsonCount(20, 'data');
        });

        it('shows unread alerts', function () {
            $user = User::factory()->create();

            Alert::factory()->unread()->count(2)->create(['user_id' => $user->id]);
            Alert::factory()->read()->count(1)->create(['user_id' => $user->id]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/alerts');

            $response->assertStatus(200);

            $data = $response->json('data');

            $unreadCount = collect($data)->filter(fn ($alert) => $alert['read_at'] === null)->count();

            expect($unreadCount)->toBe(2);
        });
    });

    describe('Mark Alerts as Read', function () {
        it('can mark specific alerts as read', function () {
            $user = User::factory()->create();

            $alert1 = Alert::factory()->unread()->create(['user_id' => $user->id]);
            $alert2 = Alert::factory()->unread()->create(['user_id' => $user->id]);
            $alert3 = Alert::factory()->unread()->create(['user_id' => $user->id]);

            $response = $this->actingAs($user)->postJson('/api/v1/me/alerts/mark-as-read', [
                'alert_ulids' => [$alert1->ulid, $alert2->ulid],
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Alerts marked as read.',
                ]);

            // Check that alerts were marked as read
            expect($alert1->fresh()->read_at)->not()->toBeNull();
            expect($alert2->fresh()->read_at)->not()->toBeNull();
            expect($alert3->fresh()->read_at)->toBeNull();
        });

        it('can mark all alerts as read', function () {
            $user = User::factory()->create();

            Alert::factory()->unread()->count(3)->create(['user_id' => $user->id]);

            $response = $this->actingAs($user)->postJson('/api/v1/me/alerts/mark-as-read');

            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Alerts marked as read.',
                ]);

            // Check that all alerts were marked as read
            $unreadCount = Alert::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();

            expect($unreadCount)->toBe(0);
        });

        it('only marks alerts belonging to authenticated user', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            $alert1 = Alert::factory()->unread()->create(['user_id' => $user1->id]);
            $alert2 = Alert::factory()->unread()->create(['user_id' => $user2->id]);

            $response = $this->actingAs($user1)->postJson('/api/v1/me/alerts/mark-as-read', [
                'alert_ulids' => [$alert1->ulid, $alert2->ulid],
            ]);

            $response->assertStatus(200);

            // Check that only user1's alert was marked as read
            expect($alert1->fresh()->read_at)->not()->toBeNull();
            expect($alert2->fresh()->read_at)->toBeNull();
        });

        it('does not update already read alerts', function () {
            $user = User::factory()->create();

            $originalReadAt = now()->subDay();

            $alert = Alert::factory()->create([
                'user_id' => $user->id,
                'read_at' => $originalReadAt,
            ]);

            $response = $this->actingAs($user)->postJson('/api/v1/me/alerts/mark-as-read', [
                'alert_ulids' => [$alert->ulid],
            ]);

            $response->assertStatus(200);

            // Check that read_at timestamp wasn't updated
            expect($alert->fresh()->read_at->timestamp)->toBe($originalReadAt->timestamp);
        });
    });

    describe('Alert Types', function () {
        it('creates game_invite alert with proper data', function () {
            $user = User::factory()->create();

            $alert = Alert::factory()->create([
                'user_id' => $user->id,
                'type' => 'game_invite',
                'data' => [
                    'game_id' => 'test-game-ulid',
                    'inviter_name' => 'John Doe',
                    'message' => 'You have been invited to a game',
                ],
            ]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/alerts');

            $response->assertStatus(200);

            $alertData = $response->json('data.0');

            expect($alertData['type'])->toBe('game_invite');
            expect($alertData['data']['game_id'])->toBe('test-game-ulid');
            expect($alertData['data']['inviter_name'])->toBe('John Doe');
        });

        it('creates level_up alert', function () {
            $user = User::factory()->create();

            $alert = Alert::factory()->create([
                'user_id' => $user->id,
                'type' => 'level_up',
                'data' => [
                    'new_level' => 5,
                    'message' => 'Congratulations! You reached level 5',
                ],
            ]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/alerts');

            $response->assertStatus(200);

            $alertData = $response->json('data.0');

            expect($alertData['type'])->toBe('level_up');
            expect($alertData['data']['new_level'])->toBe(5);
        });
    });
});
