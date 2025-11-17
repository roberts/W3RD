<?php

use App\Models\User;

describe('Password Confirmation', function () {
    it('renders confirm password screen', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.confirm'));

        $response->assertStatus(200);
    });
});
