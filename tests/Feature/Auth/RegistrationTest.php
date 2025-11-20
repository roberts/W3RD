<?php

use App\Models\Access\Client;

describe('Registration', function () {
    describe('Registration Screen', function () {
        it('can be rendered', function () {
            $response = $this->get(route('register'));

            $response->assertStatus(200);
        });
    });

    describe('User Registration', function () {
        it('allows new users to register', function () {
            $client = Client::factory()->withTrademarks()->create();

            $response = $this->post(route('register.store'), [
                'name' => 'John Doe',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'api_key' => $client->api_key,
            ]);

            $response->assertSessionHasNoErrors()
                ->assertRedirect(route('dashboard', absolute: false));

            $this->assertAuthenticated();
        });
    });
});
