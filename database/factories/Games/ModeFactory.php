<?php

namespace Database\Factories\Games;

use App\Enums\GameTitle;
use App\Models\Games\Mode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mode>
 */
class ModeFactory extends Factory
{
    protected $model = Mode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->randomElement(GameTitle::cases());
        $modeSlug = fake()->randomElement(['standard', 'blitz', 'rapid']);

        return [
            'title_slug' => $title,
            'slug' => $modeSlug,
            'name' => ucfirst($modeSlug).' Mode',
            'is_active' => true,
        ];
    }

    /**
     * Create a Connect Four standard mode.
     */
    public function connectFourStandard(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_slug' => GameTitle::CONNECT_FOUR,
            'slug' => 'standard',
            'name' => 'Standard Mode',
        ]);
    }

    /**
     * Create a Connect Four blitz mode.
     */
    public function connectFourBlitz(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_slug' => GameTitle::CONNECT_FOUR,
            'slug' => 'blitz',
            'name' => 'Blitz Mode',
        ]);
    }

    /**
     * Create an inactive mode.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
