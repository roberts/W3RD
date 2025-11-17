<?php

namespace Database\Factories\Content;

use App\Enums\AvatarType;
use App\Models\Auth\User;
use App\Models\Content\Avatar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Content\Avatar>
 */
class AvatarFactory extends Factory
{
    protected $model = Avatar::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'image_id' => null, // Would need Media package's ImageFactory
            'type' => fake()->randomElement(AvatarType::cases()),
            'creator_id' => User::factory(),
        ];
    }

    /**
     * Create a system avatar.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AvatarType::FREE,
            'name' => 'System Avatar '.fake()->numberBetween(1, 100),
        ]);
    }

    /**
     * Create a premium avatar.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AvatarType::PREMIUM,
            'name' => 'Premium '.fake()->word(),
        ]);
    }

    /**
     * Create a custom user avatar (NFT).
     */
    public function custom(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AvatarType::NFT,
            'name' => 'NFT Avatar',
        ]);
    }
}
