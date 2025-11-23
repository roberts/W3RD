<?php

namespace Database\Factories\Games;

use App\Models\Access\Client;
use App\Models\Auth\User;
use App\Models\Games\Game;
use App\Models\Games\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Games\Player>
 */
class PlayerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Games\Player>
     */
    protected $model = Player::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'user_id' => User::factory(),
            'client_id' => Client::factory()->withTrademarks(),
            'position_id' => 1,
            'color' => 'red',
        ];
    }

    /**
     * Set position for the player.
     */
    public function position(int $position): static
    {
        $colors = ['red', 'yellow', 'blue', 'green'];

        return $this->state(fn (array $attributes) => [
            'position_id' => $position,
            'color' => $colors[($position - 1) % count($colors)],
        ]);
    }
}
