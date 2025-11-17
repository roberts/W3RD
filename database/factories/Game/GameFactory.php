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
     * Defaults to ValidateFour Standard mode if not specified.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Default to ValidateFour Standard mode
        $mode = Mode::firstOrCreate(
            [
                'title_slug' => GameTitle::VALIDATE_FOUR->value,
                'slug' => 'standard',
            ],
            [
                'name' => 'Standard (7x6)',
                'is_active' => true,
            ]
        );

        return [
            'title_slug' => GameTitle::VALIDATE_FOUR,
            'mode_id' => $mode->id,
            'status' => 'pending',
            'creator_id' => User::factory(),
            'turn_number' => 1,
            'game_state' => [],
            'expires_at' => now()->addHours(24),
            'player_count' => 2,
            'action_count' => 0,
        ];
    }

    /**
     * Set the game title and mode.
     *
     * Example: Game::factory()->forTitle(GameTitle::VALIDATE_FOUR, 'standard')->create()
     *
     * @param  GameTitle  $title  The game title
     * @param  string  $modeSlug  The mode slug (e.g., 'standard', 'five', 'pop-out')
     */
    public function forTitle(GameTitle $title, string $modeSlug = 'standard'): static
    {
        return $this->state(function (array $attributes) use ($title, $modeSlug) {
            // Find or create the mode
            $mode = Mode::where('title_slug', $title->value)
                ->where('slug', $modeSlug)
                ->first();

            if (! $mode) {
                // Create mode if it doesn't exist (useful for testing)
                $mode = Mode::create([
                    'title_slug' => $title->value,
                    'slug' => $modeSlug,
                    'name' => ucfirst($modeSlug),
                    'is_active' => true,
                ]);
            }

            return [
                'title_slug' => $title,
                'mode_id' => $mode->id,
            ];
        });
    }

    /**
     * Shorthand for ValidateFour with specific mode.
     *
     * Example: Game::factory()->validateFour('pop-out')->create()
     */
    public function validateFour(string $modeSlug = 'standard'): static
    {
        return $this->forTitle(GameTitle::VALIDATE_FOUR, $modeSlug);
    }

    /**
     * Create a ValidateFour game with proper initial game state.
     * Players must be created separately and their ULIDs passed in.
     *
     * Example:
     * ```php
     * $game = Game::factory()->withValidateFourState($player1->ulid, $player2->ulid)->create();
     * ```
     */
    public function withValidateFourState(string $player1Ulid, string $player2Ulid, int $columns = 7, int $rows = 6, int $connectCount = 4): static
    {
        return $this->state(fn (array $attributes) => [
            'game_state' => [
                'board' => array_fill(0, $rows, array_fill(0, $columns, null)),
                'current_player_ulid' => $player1Ulid,
                'winner_ulid' => null,
                'columns' => $columns,
                'rows' => $rows,
                'connect_count' => $connectCount,
                'players' => [
                    $player1Ulid => ['ulid' => $player1Ulid, 'position' => 1, 'color' => 'red'],
                    $player2Ulid => ['ulid' => $player2Ulid, 'position' => 2, 'color' => 'yellow'],
                ],
                'phase' => 'active',
                'status' => 'active',
                'is_draw' => false,
            ],
        ]);
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
            'status' => 'completed',
            'started_at' => now()->subHours(1),
            'finished_at' => now(),
            'duration_seconds' => 3600,
        ]);
    }

    /**
     * Indicate that the game is pending (default state).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
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
