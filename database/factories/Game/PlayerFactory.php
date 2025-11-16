<?php

namespace Database\Factories\Game;

use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Player>
 */
class PlayerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Game\Player>
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
            'name' => fake()->firstName(),
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
