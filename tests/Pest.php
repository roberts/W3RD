<?php

use App\Models\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Integration', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toHaveUserStructure', function () {
    return $this->toHaveKeys([
        'id', 'ulid', 'name', 'email', 'avatar_url',
        'created_at', 'updated_at',
    ]);
});

expect()->extend('toHaveGameStructure', function () {
    return $this->toHaveKeys([
        'ulid', 'title_slug', 'status', 'current_turn',
        'game_state', 'created_at',
    ]);
});

expect()->extend('toHaveSubscriptionStructure', function () {
    return $this->toHaveKeys([
        'id', 'user_id', 'platform', 'status',
        'created_at', 'updated_at',
    ]);
});

expect()->extend('toBeSuccessfulApiResponse', function () {
    $this->value->assertSuccessful();
    $this->value->assertJson([]);

    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create and authenticate a user for API requests
 */
function createAuthenticatedUser(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

/**
 * Assert validation error in JSON response
 */
function assertValidationError(TestResponse $response, string $field): void
{
    $response->assertStatus(422)
        ->assertJsonValidationErrors($field);
}

/**
 * Assert standard JSON API error structure
 */
function assertApiError(TestResponse $response, int $status, string $message): void
{
    $response->assertStatus($status)
        ->assertJson(['message' => $message]);
}
