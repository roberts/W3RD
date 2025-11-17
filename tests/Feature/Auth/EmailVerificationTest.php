<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

describe('Email Verification', function () {
    describe('Verification Screen', function () {
        it('can be rendered', function () {
            $user = User::factory()->unverified()->create();

            $response = $this->actingAs($user)->get(route('verification.notice'));

            $response->assertStatus(200);
        });
    });

    describe('Email Verification Process', function () {
        it('verifies email with valid hash', function () {
            $user = User::factory()->unverified()->create();

            Event::fake();

            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)]
            );

            $response = $this->actingAs($user)->get($verificationUrl);

            Event::assertDispatched(Verified::class);

            expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
            $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
        });

        it('does not verify email with invalid hash', function () {
            $user = User::factory()->unverified()->create();

            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1('wrong-email')]
            );

            $this->actingAs($user)->get($verificationUrl);

            expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
        });

        it('redirects already verified users without firing event again', function () {
            $user = User::factory()->create([
                'email_verified_at' => now(),
            ]);

            Event::fake();

            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)]
            );

            $this->actingAs($user)->get($verificationUrl)
                ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

            expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
            Event::assertNotDispatched(Verified::class);
        });
    });
});
