<?php

use App\Models\Auth\User;
use Laravel\Fortify\Features;

describe('Two-Factor Challenge', function () {
    describe('Access Control', function () {
        it('redirects to login when not authenticated', function () {
            if (! Features::canManageTwoFactorAuthentication()) {
                $this->markTestSkipped('Two-factor authentication is not enabled.');
            }

            $response = $this->get(route('two-factor.login'));

            $response->assertRedirect(route('login'));
        });
    });

    describe('Challenge Screen', function () {
        it('can be rendered', function () {
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

            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect(route('two-factor.login'));
        });
    });
});
