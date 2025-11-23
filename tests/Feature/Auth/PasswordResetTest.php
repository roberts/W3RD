<?php

use App\Models\Auth\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

describe('Password Reset', function () {
    describe('Request Reset Link', function () {
        it('renders reset password link screen', function () {
            $response = $this->get(route('password.request'));

            $response->assertStatus(200);
        });

        it('allows reset password link to be requested', function () {
            Notification::fake();

            $user = User::factory()->create();

            $this->post(route('password.request'), ['email' => $user->email]);

            Notification::assertSentTo($user, ResetPassword::class);
        });
    });

    describe('Reset Password', function () {
        it('renders reset password screen', function () {
            Notification::fake();

            $user = User::factory()->create();

            $this->post(route('password.request'), ['email' => $user->email]);

            Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
                $response = $this->get(route('password.reset', $notification->token));

                $response->assertStatus(200);

                return true;
            });
        });

        it('resets password with valid token', function () {
            Notification::fake();

            $user = User::factory()->create();

            $this->post(route('password.request'), ['email' => $user->email]);

            Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
                $response = $this->post(route('password.update'), [
                    'token' => $notification->token,
                    'email' => $user->email,
                    'password' => 'password',
                    'password_confirmation' => 'password',
                ]);

                $response
                    ->assertSessionHasNoErrors()
                    ->assertRedirect(route('login', absolute: false));

                return true;
            });
        });
    });
});
