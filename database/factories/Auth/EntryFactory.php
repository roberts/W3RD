<?php

namespace Database\Factories\Auth;

use App\Models\Access\Client;
use App\Models\Auth\Entry;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Auth\Entry>
 */
class EntryFactory extends Factory
{
    protected $model = Entry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $loggedIn = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory()->withTrademarks(),
            'ip_address' => fake()->ipv4(),
            'device_info' => fake()->userAgent(),
            'token_id' => fake()->optional()->numerify('################'),
            'logged_in_at' => $loggedIn,
            'logged_out_at' => fake()->optional(0.7)->dateTimeBetween($loggedIn, 'now'),
        ];
    }

    /**
     * Create an active session (not logged out yet).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'logged_in_at' => fake()->dateTimeBetween('-2 hours', 'now'),
            'logged_out_at' => null,
        ]);
    }

    /**
     * Create a mobile device entry.
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'device_info' => fake()->randomElement([
                'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
                'Mozilla/5.0 (Linux; Android 11; SM-G991B)',
            ]),
        ]);
    }
}
