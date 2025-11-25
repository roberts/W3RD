<?php

use App\Models\Auth\User;
use Livewire\Volt\Volt;

describe('Profile Management', function () {
    describe('Profile Page', function () {
        it('displays profile page', function () {
            $this->actingAs($user = User::factory()->create());

            $this->get(route('profile.edit'))->assertOk();
        });
    });

    describe('Profile Information Update', function () {
        it('updates profile information successfully', function () {
            $user = User::factory()->create();

            $this->actingAs($user);

            $response = Volt::test('settings.profile')
                ->set('name', 'Test User')
                ->set('email', 'test@example.com')
                ->call('updateProfileInformation');

            $response->assertHasNoErrors();

            $user->refresh();

            expect($user->name)->toEqual('Test User');
            expect($user->email)->toEqual('test@example.com');
            expect($user->email_verified_at)->toBeNull();
        });

        it('keeps email verification status when email is unchanged', function () {
            $user = User::factory()->create();

            $this->actingAs($user);

            $response = Volt::test('settings.profile')
                ->set('name', 'Test User')
                ->set('email', $user->email)
                ->call('updateProfileInformation');

            $response->assertHasNoErrors();

            expect($user->refresh()->email_verified_at)->not->toBeNull();
        });
    });

    describe('Account Deletion', function () {
        it('allows users to delete their account', function () {
            $user = User::factory()->create();

            $this->actingAs($user);

            $response = Volt::test('settings.delete-user-form')
                ->set('password', 'password')
                ->call('deleteUser');

            $response
                ->assertHasNoErrors()
                ->assertRedirect('/');

            $this->assertSoftDeleted($user);
            expect(auth()->check())->toBeFalse();
        });

        it('requires correct password to delete account', function () {
            $user = User::factory()->create();

            $this->actingAs($user);

            $response = Volt::test('settings.delete-user-form')
                ->set('password', 'wrong-password')
                ->call('deleteUser');

            $response->assertHasErrors(['password']);

            expect($user->fresh())->not->toBeNull();
        });
    });
});
