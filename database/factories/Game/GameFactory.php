<?php

namespace Database\Factories\Game;

use App\Enums\GameTitle;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Mode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Game>
 */
class GameFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Game\Game>
     */
    protected $model = Game::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title_slug' => GameTitle::VALIDATE_FOUR,
            'mode_id' => Mode::where('title_slug', GameTitle::VALIDATE_FOUR->value)
                ->where('slug', 'standard')
                ->first()
                ->id ?? 1,
            'status' => 'pending',
            'creator_id' => User::factory(),
            'turn_number' => 1,
            'game_state' => json_encode([]),
            'expires_at' => now()->addHours(24),
            'player_count' => 2,
            'action_count' => 0,
        ];
    }

    /**
     * Indicate that the game is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the game is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'finished',
            'started_at' => now()->subHours(1),
            'finished_at' => now(),
            'duration_seconds' => 3600,
        ]);
    }

    /**
     * Set a specific mode for the game.
     */
    public function withMode(string $titleSlug, string $modeSlug): static
    {
        return $this->state(function (array $attributes) use ($titleSlug, $modeSlug) {
            $mode = Mode::where('title_slug', $titleSlug)
                ->where('slug', $modeSlug)
                ->first();

            return [
                'mode_id' => $mode->id ?? $attributes['mode_id'],
            ];
        });
    }
}
