<?php

namespace Database\Factories\Game;

use App\Enums\GameTitle;
use App\Enums\LobbyStatus;
use App\Models\Auth\User;
use App\Models\Lobby;
use Illuminate\Database\Eloquent\Factories\Factory;

class LobbyFactory extends Factory
{
    protected $model = Lobby::class;

    public function definition(): array
    {
        return [
            'game_title' => fake()->randomElement(GameTitle::cases()),
            'game_mode' => null,
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
