<?php

namespace Database\Factories\Game;

use App\Enums\GameTitle;
use App\Models\Game\Mode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Mode>
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
     * Create a Validate Four standard mode.
     */
    public function validateFourStandard(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_slug' => GameTitle::VALIDATE_FOUR,
            'slug' => 'standard',
            'name' => 'Standard Mode',
        ]);
    }

    /**
     * Create a Validate Four blitz mode.
     */
    public function validateFourBlitz(): static
    {
        return $this->state(fn (array $attributes) => [
            'title_slug' => GameTitle::VALIDATE_FOUR,
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
