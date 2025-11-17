<?php

namespace Tests\Feature\Helpers;

use Illuminate\Testing\TestResponse;

class AssertionHelper
{
    /**
     * Assert that the response has a specific JSON structure.
     */
    public static function assertJsonStructure(TestResponse $response, array $structure): void
    {
        $response->assertJsonStructure($structure);
    }

    /**
     * Assert that the response contains a validation error for a specific field.
     */
    public static function assertValidationError(TestResponse $response, string $field, ?string $message = null): void
    {
        $response->assertStatus(422)
            ->assertJsonValidationErrors($field);

        if ($message) {
            $response->assertJsonFragment(['message' => $message]);
        }
    }

    /**
     * Assert that the response is a successful API response with expected structure.
     */
    public static function assertSuccessfulApiResponse(TestResponse $response, int $status = 200): void
    {
        $response->assertStatus($status)
            ->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Assert that the response contains an API error.
     */
    public static function assertApiError(TestResponse $response, int $status, ?string $message = null): void
    {
        $response->assertStatus($status);

        if ($message) {
            $response->assertJsonFragment(['message' => $message]);
        }
    }

    /**
     * Assert that the response has pagination metadata.
     */
    public static function assertHasPagination(TestResponse $response): void
    {
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }

    /**
     * Assert that the response data has a specific user structure.
     */
    public static function assertHasUserStructure(TestResponse $response, ?string $path = 'user'): void
    {
        $structure = [
            'username',
            'name',
            'email',
        ];

        if ($path) {
            $response->assertJsonStructure([$path => $structure]);
        } else {
            $response->assertJsonStructure($structure);
        }
    }

    /**
     * Assert that the response data has a specific game structure.
     */
    public static function assertHasGameStructure(TestResponse $response, ?string $path = 'data'): void
    {
        $structure = [
            'ulid',
            'game_title',
            'status',
            'turn_number',
            'players',
        ];

        if ($path) {
            $response->assertJsonStructure([$path => $structure]);
        } else {
            $response->assertJsonStructure($structure);
        }
    }

    /**
     * Assert that the response data has a specific subscription structure.
     */
    public static function assertHasSubscriptionStructure(TestResponse $response, ?string $path = 'subscription'): void
    {
        $structure = [
            'plan',
            'status',
            'current_period_start',
            'current_period_end',
        ];

        if ($path) {
            $response->assertJsonStructure([$path => $structure]);
        } else {
            $response->assertJsonStructure($structure);
        }
    }

    /**
     * Assert that the response contains specific data values.
     */
    public static function assertJsonContains(TestResponse $response, array $data): void
    {
        foreach ($data as $key => $value) {
            $response->assertJsonPath($key, $value);
        }
    }

    /**
     * Assert unauthorized access.
     */
    public static function assertUnauthorized(TestResponse $response, ?string $message = 'Unauthenticated.'): void
    {
        self::assertApiError($response, 401, $message);
    }

    /**
     * Assert forbidden access.
     */
    public static function assertForbidden(TestResponse $response, ?string $message = null): void
    {
        self::assertApiError($response, 403, $message);
    }

    /**
     * Assert not found.
     */
    public static function assertNotFound(TestResponse $response): void
    {
        $response->assertStatus(404);
    }
}
