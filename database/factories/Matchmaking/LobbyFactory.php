<?php

namespace Database\Factories\Matchmaking;

use App\Matchmaking\Enums\LobbyStatus;
use App\Models\Auth\User;
use App\Models\Games\Mode;
use App\Models\Matchmaking\Lobby;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lobby>
 */
class LobbyFactory extends Factory
{
    protected $model = Lobby::class;

    public function definition(): array
    {
        // Use seeded mode - assumes ModeSeeder has been run
        $mode = Mode::connectFour();

        return [
            'title_slug' => $mode->title_slug,
            'mode_id' => $mode->id,
            'host_id' => User::factory(),
            'is_public' => fake()->boolean(),
            'min_players' => fake()->numberBetween(2, 4),
            'scheduled_at' => null,
            'status' => LobbyStatus::PENDING,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 week'),
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LobbyStatus::READY,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LobbyStatus::COMPLETED,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LobbyStatus::CANCELLED,
        ]);
    }
}
