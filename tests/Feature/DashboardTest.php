<?php

use App\Models\Auth\User;

describe('Dashboard', function () {
    describe('Guest Access', function () {
        it('redirects guests to the login page', function () {
            $response = $this->get(route('dashboard'));
            $response->assertRedirect(route('login'));
        });
    });

    describe('Authenticated Access', function () {
        it('allows authenticated users to visit the dashboard', function () {
            $user = User::factory()->create();
            $this->actingAs($user);

            $response = $this->get(route('dashboard'));
            $response->assertStatus(200);
        });
    });
});
