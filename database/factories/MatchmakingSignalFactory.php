<?php

namespace Database\Factories;

use App\Enums\GameTitle;
use App\Models\Auth\User;
use App\Models\MatchmakingSignal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\MatchmakingSignal>
 */
class MatchmakingSignalFactory extends Factory
{
    protected $model = MatchmakingSignal::class;

    public function definition(): array
    {
    $gameTitle = $this->faker->randomElement(GameTitle::cases());
    $gameMode = $this->faker->randomElement(['standard', 'blitz', 'rapid']);

        return [
            'ulid' => (string) Str::ulid(),
            'user_id' => User::factory(),
            'game_preference' => $gameTitle->value,
            'skill_rating' => $this->faker->numberBetween(1, 5000),
            'status' => 'active',
            'preferences' => [
                'game_mode' => $gameMode,
                'region' => $this->faker->randomElement(['na-east', 'na-west', 'eu-central']),
            ],
            'expires_at' => now()->addMinutes(5),
        ];
    }

    public function cancelled(): self
    {
        return $this->state(fn () => [
            'status' => 'cancelled',
            'expires_at' => now()->subMinute(),
        ]);
    }
}
