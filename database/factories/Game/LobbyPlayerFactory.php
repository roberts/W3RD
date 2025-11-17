<?php

namespace Database\Factories\Game;

use App\Enums\LobbyPlayerStatus;
use App\Models\Auth\User;
use App\Models\Lobby;
use App\Models\LobbyPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;

class LobbyPlayerFactory extends Factory
{
    protected $model = LobbyPlayer::class;

    public function definition(): array
    {
        return [
            'lobby_id' => Lobby::factory(),
            'user_id' => User::factory(),
            'status' => LobbyPlayerStatus::PENDING,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LobbyPlayerStatus::ACCEPTED,
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LobbyPlayerStatus::DECLINED,
        ]);
    }
}
