<?php

namespace Database\Factories\Auth;

use App\Models\Access\Client;
use App\Models\Auth\Registration;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Auth\Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'verification_token' => Str::random(64),
            'user_id' => null,
        ];
    }

    /**
     * Create a verified registration (with user_id).
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
        ]);
    }

    /**
     * Create an unverified registration.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }
}
