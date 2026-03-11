<?php

namespace Database\Factories\Matchmaking;

use App\Enums\GameTitle;
use App\Matchmaking\Enums\QueueSlotStatus;
use App\Models\Auth\User;
use App\Models\Games\Mode;
use App\Models\Matchmaking\QueueSlot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QueueSlot>
 */
class QueueSlotFactory extends Factory
{
    protected $model = QueueSlot::class;

    public function definition(): array
    {
        $gameTitle = $this->faker->randomElement(GameTitle::cases());
        $gameMode = $this->faker->randomElement(['standard', 'blitz', 'rapid']);

        // Create or find a mode for the selected game title
        $mode = Mode::firstOrCreate(
            [
                'title_slug' => $gameTitle->value,
                'slug' => 'standard',
            ],
            [
                'name' => 'Standard',
                'is_active' => true,
            ]
        );

        return [
            'ulid' => (string) Str::ulid(),
            'user_id' => User::factory(),
            'title_slug' => $gameTitle->value,
            'mode_id' => $mode->id,
            'skill_rating' => $this->faker->numberBetween(1, 5000),
            'status' => QueueSlotStatus::ACTIVE,
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
            'status' => QueueSlotStatus::CANCELLED,
            'expires_at' => now()->subMinute(),
        ]);
    }
}
