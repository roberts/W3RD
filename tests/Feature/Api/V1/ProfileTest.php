<?php

use App\Models\Auth\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

describe('Profile Management', function () {
    beforeEach(function () {
        // Seed the username update permission for all tests
        Permission::firstOrCreate(['name' => 'can-update-username', 'guard_name' => 'web']);
    });

    describe('Profile Retrieval', function () {
        it('shows authenticated user profile', function () {
            $user = User::factory()->create([
                'name' => 'John Doe',
                'bio' => 'Test bio',
                'social_links' => ['twitter' => '@johndoe'],
            ]);

            $response = $this->actingAs($user)->getJson('/api/v1/me/profile');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'name' => 'John Doe',
                        'username' => $user->username,
                        'avatar' => null,
                        'bio' => 'Test bio',
                        'social_links' => ['twitter' => '@johndoe'],
                    ],
                ]);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/me/profile');

            $response->assertStatus(401);
        });
    });

    describe('Profile Update', function () {
        it('updates name successfully', function () {
            $user = User::factory()->create(['name' => 'Old Name']);

            $response = $this->actingAs($user)->patchJson('/api/v1/me/profile', [
                'name' => 'New Name',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'name' => 'New Name',
                    ],
                ]);

            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'name' => 'New Name',
            ]);
        });

        it('updates bio successfully', function () {
            $user = User::factory()->create(['bio' => 'Old bio']);

            $response = $this->actingAs($user)->patchJson('/api/v1/me/profile', [
                'bio' => 'New bio text',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'bio' => 'New bio text',
                    ],
                ]);

            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'bio' => 'New bio text',
            ]);
        });

        it('updates social links successfully', function () {
            $user = User::factory()->create();

            $socialLinks = [
                'twitter' => '@testuser',
                'discord' => 'testuser#1234',
            ];

            $response = $this->actingAs($user)->patchJson('/api/v1/me/profile', [
                'social_links' => $socialLinks,
            ]);

            $response->assertStatus(200);

            $this->assertDatabaseHas('users', [
                'id' => $user->id,
            ]);

            expect($user->fresh()->social_links)->toEqual($socialLinks);
        });

        it('validates name length', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->patchJson('/api/v1/me/profile', [
                'name' => 'ab', // Too short
            ]);

            $response->assertStatus(422);
            // Check custom error format
            $errors = $response->json('errors');
            $hasNameError = collect($errors)->contains('field', 'name');
            expect($hasNameError)->toBeTrue();
        });
    });

    describe('Username Update', function () {
        it('updates username with permission', function () {
            $user = User::factory()->create();

            // Create and assign permission
            $permission = Permission::firstOrCreate(['name' => 'can-update-username']);
            $role = Role::firstOrCreate(['name' => 'test-role']);
            $role->givePermissionTo($permission);
            $user->assignRole($role);

            $response = $this->actingAs($user)->patchJson('/api/v1/me/profile', [
                'username' => 'newusername',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'username' => 'newusername',
                    ],
                ]);

            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'username' => 'newusername',
            ]);
        });

        it('prevents username update without permission', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->patchJson('/api/v1/me/profile', [
                'username' => 'newusername',
            ]);

            $response->assertStatus(403)
                ->assertJson([
                    'message' => 'You do not have permission to update your username.',
                ]);
        });

        it('validates username uniqueness', function () {
            // Create first user and manually set their username
            $existingUser = User::factory()->create();
            $existingUser->update(['username' => 'takenusername']);

            $user = User::factory()->create();

            // Give permission
            $permission = Permission::firstOrCreate(['name' => 'can-update-username']);
            $role = Role::firstOrCreate(['name' => 'test-role']);
            $role->givePermissionTo($permission);
            $user->assignRole($role);

            $response = $this->actingAs($user)->patchJson('/api/v1/me/profile', [
                'username' => 'takenusername',
            ]);

            $response->assertStatus(422);
            // Check custom error format
            $errors = $response->json('errors');
            $hasUsernameError = collect($errors)->contains('field', 'username');
            expect($hasUsernameError)->toBeTrue();
        });

        it('validates username length', function () {
            $user = User::factory()->create();

            // Give permission
            $permission = Permission::firstOrCreate(['name' => 'can-update-username']);
            $role = Role::firstOrCreate(['name' => 'test-role']);
            $role->givePermissionTo($permission);
            $user->assignRole($role);

            $response = $this->actingAs($user)->patchJson('/api/v1/me/profile', [
                'username' => 'ab', // Too short (min 3)
            ]);

            $response->assertStatus(422);
            // Check custom error format
            $errors = $response->json('errors');
            $hasUsernameError = collect($errors)->contains('field', 'username');
            expect($hasUsernameError)->toBeTrue();
        });
    });
});
