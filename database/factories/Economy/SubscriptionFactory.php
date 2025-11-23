<?php

namespace Database\Factories\Economy;

use App\Models\Auth\User;
use App\Models\Economy\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Economy\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'billable_id' => User::factory(),
            'billable_type' => User::class,
            'type' => 'premium',
            'stripe_id' => 'sub_'.fake()->unique()->regexify('[A-Za-z0-9]{14}'),
            'stripe_status' => 'active',
            'stripe_price' => 'price_'.fake()->regexify('[A-Za-z0-9]{14}'),
            'quantity' => 1,
            'trial_ends_at' => null,
            'ends_at' => null,
            'provider' => 'stripe',
        ];
    }

    /**
     * Create a trialing subscription.
     */
    public function trialing(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'trialing',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    /**
     * Create a canceled subscription.
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_status' => 'canceled',
            'ends_at' => now()->addDays(30),
        ]);
    }

    /**
     * Create an Apple subscription.
     */
    public function apple(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'apple',
            'stripe_id' => fake()->unique()->numerify('################'),
        ]);
    }

    /**
     * Create a Google subscription.
     */
    public function google(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'google',
            'stripe_id' => fake()->unique()->regexify('[a-z0-9]{32}'),
        ]);
    }

    /**
     * Create a Telegram subscription.
     */
    public function telegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'telegram',
        ]);
    }

    /**
     * Create an admin-granted subscription.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'admin',
            'stripe_id' => 'admin_'.fake()->uuid(),
        ]);
    }
}
