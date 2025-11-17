<?php

use App\Models\Auth\User;
use App\Models\Billing\Subscription;

describe('Billing & Subscription Management', function () {
    describe('Plans & Status', function () {
        it('lists available subscription plans', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->getJson('/api/v1/billing/plans');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'price',
                            'features',
                        ],
                    ],
                ]);

            expect($response->json('data'))->toBeArray();
            expect(count($response->json('data')))->toBeGreaterThan(0);
        });

        it('shows subscription status for unsubscribed user', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->getJson('/api/v1/billing/status');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'subscribed' => false,
                        'plan' => 'basic',
                    ],
                ]);
        });

        it('shows subscription status for subscribed user', function () {
            $user = User::factory()->create();

            Subscription::factory()->create([
                'user_id' => $user->id,
                'type' => 'premium',
                'stripe_status' => 'active',
                'provider' => 'stripe',
            ]);

            $response = $this->actingAs($user)->getJson('/api/v1/billing/status');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'subscribed' => true,
                        'plan' => 'premium',
                        'provider' => 'stripe',
                        'status' => 'active',
                    ],
                ]);
        });
    });

    describe('Stripe Subscription', function () {
        it('creates subscription checkout session with valid plan', function () {
            $user = User::factory()->create();

            // Mock Stripe checkout to avoid actual API calls
            $this->markTestSkipped('Requires Stripe API mocking');

            $response = $this->actingAs($user)->postJson('/api/v1/billing/subscribe', [
                'plan' => 'premium',
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'checkout_url',
                    ],
                ]);
        });

        it('rejects invalid plan', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/v1/billing/subscribe', [
                'plan' => 'invalid-plan',
            ]);

            $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Invalid plan selected.',
                ]);
        });
    });

    describe('IAP Verification', function () {
        it('verifies Apple receipt successfully', function () {
            $user = User::factory()->create();

            // Mock Apple receipt validation
            $this->markTestSkipped('Requires Apple receipt validation mocking');

            $response = $this->actingAs($user)->postJson('/api/v1/billing/apple/verify', [
                'transaction_id' => 'test_transaction_id',
            ]);

            $response->assertStatus(200);

            $this->assertDatabaseHas('subscriptions', [
                'user_id' => $user->id,
                'provider' => 'apple',
                'stripe_status' => 'active',
            ]);
        });

        it('verifies Google receipt successfully', function () {
            $user = User::factory()->create();

            // Mock Google receipt validation
            $this->markTestSkipped('Requires Google receipt validation mocking');

            $response = $this->actingAs($user)->postJson('/api/v1/billing/google/verify', [
                'purchase_token' => 'test_purchase_token',
            ]);

            $response->assertStatus(200);

            $this->assertDatabaseHas('subscriptions', [
                'user_id' => $user->id,
                'provider' => 'google',
                'stripe_status' => 'active',
            ]);
        });

        it('verifies Telegram receipt successfully', function () {
            $user = User::factory()->create();

            // Mock Telegram receipt validation
            $this->markTestSkipped('Requires Telegram receipt validation mocking');

            $response = $this->actingAs($user)->postJson('/api/v1/billing/telegram/verify', [
                'payment_id' => 'test_payment_id',
            ]);

            $response->assertStatus(200);

            $this->assertDatabaseHas('subscriptions', [
                'user_id' => $user->id,
                'provider' => 'telegram',
                'stripe_status' => 'active',
            ]);
        });

        it('rejects invalid Apple receipt', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson('/api/v1/billing/apple/verify', [
                'transaction_id' => '',
            ]);

            $response->assertStatus(422);
        });
    });

    describe('Subscription Management', function () {
        it('returns Stripe customer portal URL', function () {
            $user = User::factory()->create();

            // Mock Stripe portal to avoid actual API calls
            $this->markTestSkipped('Requires Stripe API mocking');

            $response = $this->actingAs($user)->postJson('/api/v1/billing/manage');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'portal_url',
                    ],
                ]);
        });
    });
});
