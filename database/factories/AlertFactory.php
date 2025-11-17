<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['game_invite', 'game_started', 'game_completed', 'level_up', 'achievement_unlocked']),
            'data' => [
                'message' => fake()->sentence(),
                'link' => fake()->optional()->url(),
            ],
            'read_at' => fake()->optional(0.3)->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the alert is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the alert is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    /**
     * Create a game invite alert.
     */
    public function gameInvite(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'game_invite',
            'data' => [
                'message' => 'You have been invited to a game',
                'game_ulid' => fake()->uuid(),
                'inviter_name' => fake()->name(),
            ],
        ]);
    }
}
