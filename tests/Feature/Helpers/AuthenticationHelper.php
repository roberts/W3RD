<?php

namespace Tests\Feature\Helpers;

use App\Models\Access\Client;
use App\Models\Auth\User;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

class AuthenticationHelper
{
    /**
     * Act as an authenticated user with Sanctum token.
     */
    public static function actingAs(User $user, array $abilities = ['*']): User
    {
        Sanctum::actingAs($user, $abilities);

        return $user;
    }

    /**
     * Login as a user and return the authentication token.
     */
    public static function loginAs(User $user, string $password = 'password'): string
    {
        $client = Client::factory()->create();

        $response = test()->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ], [
            'X-Client-Key' => $client->id,
        ]);

        return $response->json('token');
    }

    /**
     * Create a Sanctum token for a user.
     */
    public static function createToken(User $user, string $name = 'test-token', array $abilities = ['*']): string
    {
        return $user->createToken($name, $abilities)->plainTextToken;
    }

    /**
     * Register a new user and return the token.
     */
    public static function registerUser(array $data = []): array
    {
        $client = Client::factory()->create();

        $userData = array_merge([
            'client_id' => $client->id,
            'username' => 'testuser' . rand(1000, 9999),
            'email' => 'test' . rand(1000, 9999) . '@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'name' => 'Test User',
        ], $data);

        // Override password_confirmation if password was provided
        if (isset($data['password']) && ! isset($data['password_confirmation'])) {
            $userData['password_confirmation'] = $data['password'];
        }

        $response = test()->postJson('/api/v1/auth/register', $userData);

        return [
            'user_data' => $userData,
            'response' => $response,
        ];
    }

    /**
     * Verify email with token.
     */
    public static function verifyEmail(string $email, string $token): TestResponse
    {
        return test()->postJson('/api/v1/auth/verify', [
            'email' => $email,
            'token' => $token,
        ]);
    }

    /**
     * Create an authenticated user ready for testing.
     */
    public static function createAuthenticatedUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        Sanctum::actingAs($user);

        return $user;
    }
}
