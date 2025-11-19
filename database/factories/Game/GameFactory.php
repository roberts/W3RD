<?php

namespace Database\Factories\Game;

use App\Enums\GameTitle;
use App\Models\Access\Client;
use App\Models\Auth\Agent;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Mode;
use App\Models\Game\Player;
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
            'completed_at' => now(),
            'duration_seconds' => 3600,
            'outcome_type' => 'win',
            'outcome_details' => ['reason' => 'factory_completed'],
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

    /**
     * Create game with players automatically.
     *
     * @param  array<User>|int  $usersOrCount  Array of User models or count to auto-create
     * @param  int|null  $clientId  Optional client ID for all players
     *
     * Example:
     * ```php
     * // With existing users
     * Game::factory()->completed()->withPlayers([$user1, $user2])->create()
     *
     * // Auto-create 2 users
     * Game::factory()->withPlayers(2)->create()
     *
     * // With specific client
     * Game::factory()->withPlayers([$user1, $user2], clientId: $client->id)->create()
     * ```
     */
    public function withPlayers(array|int $usersOrCount, ?int $clientId = null): static
    {
        return $this->afterCreating(function (Game $game) use ($usersOrCount, $clientId) {
            $users = is_int($usersOrCount)
                ? User::factory()->count($usersOrCount)->create()
                : collect($usersOrCount);

            $users->each(function (User $user, int $index) use ($game, $clientId) {
                $colors = ['red', 'yellow', 'blue', 'green'];

                Player::factory()->create([
                    'game_id' => $game->getKey(),
                    'user_id' => $user->getKey(),
                    'position_id' => $index + 1,
                    'color' => $colors[$index % count($colors)],
                    'client_id' => $clientId ?? Client::factory(),
                ]);
            });
        });
    }

    /**
     * Create game with an agent as opponent.
     *
     * @param  User  $humanUser  The human player
     * @param  string|null  $gameTitle  Game title for agent compatibility (defaults to 'validate-four')
     * @param  int|null  $clientId  Optional client ID
     *
     * Returns the created game with agent_user and agent properties attached.
     *
     * Example:
     * ```php
     * $game = Game::factory()->completed()->withAgentOpponent($humanUser)->create()
     * $agentUser = $game->agent_user; // Access the created agent user
     * $agent = $game->agent; // Access the created agent
     * ```
     */
    public function withAgentOpponent(User $humanUser, ?string $gameTitle = null, ?int $clientId = null): static
    {
        return $this->afterCreating(function (Game $game) use ($humanUser, $gameTitle, $clientId) {
            $gameTitle = $gameTitle ?? 'validate-four';

            // Create agent
            $agent = Agent::factory()
                ->forGame($gameTitle)
                ->alwaysAvailable()
                ->create();

            // Create agent user
            $agentUser = User::factory()->create(['agent_id' => $agent->getKey()]);

            // Create players
            $colors = ['red', 'yellow'];

            Player::factory()->create([
                'game_id' => $game->getKey(),
                'user_id' => $humanUser->getKey(),
                'position_id' => 1,
                'color' => $colors[0],
                'client_id' => $clientId ?? Client::factory(),
            ]);

            Player::factory()->create([
                'game_id' => $game->getKey(),
                'user_id' => $agentUser->getKey(),
                'position_id' => 2,
                'color' => $colors[1],
                'client_id' => $clientId ?? Client::factory(),
            ]);

            // Attach agent properties to game for easy access
            /** @var Agent $agent */
            /** @var User $agentUser */
            $game->agent_user = $agentUser; // @phpstan-ignore-line
            $game->agent = $agent; // @phpstan-ignore-line
        });
    }
}
