<?php

namespace Database\Factories\Gamification;

use App\Models\Auth\User;
use App\Models\Gamification\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gamification\Badge>
 */
class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'image_id' => null, // Would need Media package's ImageFactory
            'condition_json' => [
                'type' => fake()->randomElement(['win_streak', 'total_wins', 'level_reached']),
                'value' => fake()->numberBetween(5, 100),
            ],
            'creator_id' => User::factory(),
        ];
    }

    /**
     * Create a win streak badge.
     */
    public function winStreak(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'win-streak-'.fake()->numberBetween(5, 20),
            'name' => 'Win Streak Champion',
            'condition_json' => [
                'type' => 'win_streak',
                'value' => 10,
            ],
        ]);
    }

    /**
     * Create a milestone badge.
     */
    public function milestone(): static
    {
        return $this->state(fn (array $attributes) => [
            'slug' => 'milestone-'.fake()->randomElement(['100', '500', '1000']),
            'name' => 'Milestone Achievement',
            'condition_json' => [
                'type' => 'total_wins',
                'value' => 100,
            ],
        ]);
    }
}
