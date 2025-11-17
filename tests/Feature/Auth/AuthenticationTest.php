<?php

use App\Models\User;
use Laravel\Fortify\Features;

describe('Auth', function () {
    describe('Login Screen', function () {
        it('can be rendered', function () {
            $response = $this->get(route('login'));

            $response->assertStatus(200);
        });
    });

    describe('Login Process', function () {
        it('allows users to authenticate using the login screen', function () {
            $user = User::factory()->withoutTwoFactor()->create();

            $response = $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('dashboard', absolute: false));

            $this->assertAuthenticated();
        });

        it('does not authenticate users with invalid password', function () {
            $user = User::factory()->create();

            $response = $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

            $response->assertSessionHasErrorsIn('email');

            $this->assertGuest();
        });
    });

    describe('Two-Factor Authentication', function () {
        it('redirects users with two factor enabled to two factor challenge', function () {
            if (! Features::canManageTwoFactorAuthentication()) {
                $this->markTestSkipped('Two-factor authentication is not enabled.');
            }

            Features::twoFactorAuthentication([
                'confirm' => true,
                'confirmPassword' => true,
            ]);

            $user = User::factory()->create([
                'two_factor_secret' => encrypt('test-secret'),
                'two_factor_recovery_codes' => encrypt('test-codes'),
                'two_factor_confirmed_at' => now(),
            ]);

            $response = $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $response->assertRedirect(route('two-factor.login'));
            $this->assertGuest();
        });
    });

    describe('Logout', function () {
        it('allows users to logout', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->post(route('logout'));

            $response->assertRedirect(route('home'));

            $this->assertGuest();
        });
    });
});
