<?php

use App\Models\User;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

describe('Two-Factor Authentication Settings', function () {
    beforeEach(function () {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);
    });

    describe('Settings Page Access', function () {
        it('renders two factor settings page', function () {
            $user = User::factory()->withoutTwoFactor()->create();

            $this->actingAs($user)
                ->withSession(['auth.password_confirmed_at' => time()])
                ->get(route('two-factor.show'))
                ->assertOk()
                ->assertSee('Two Factor Authentication')
                ->assertSee('Disabled');
        });

        it('requires password confirmation when enabled', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)
                ->get(route('two-factor.show'));

            $response->assertRedirect(route('password.confirm'));
        });

        it('returns forbidden response when two factor is disabled', function () {
            config(['fortify.features' => []]);

            $user = User::factory()->create();

            $response = $this->actingAs($user)
                ->withSession(['auth.password_confirmed_at' => time()])
                ->get(route('two-factor.show'));

            $response->assertForbidden();
        });
    });

    describe('Two-Factor Management', function () {
        it('disables two factor authentication when confirmation is abandoned', function () {
            $user = User::factory()->create();

            $user->forceFill([
                'two_factor_secret' => encrypt('test-secret'),
                'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
                'two_factor_confirmed_at' => null,
            ])->save();

            $this->actingAs($user);

            $component = Volt::test('settings.two-factor');

            $component->assertSet('twoFactorEnabled', false);

            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ]);
        });
    });
});
