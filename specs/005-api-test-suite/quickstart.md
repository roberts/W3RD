# Quickstart: API Test Suite

**Feature**: 005-api-test-suite  
**Date**: 2025-01-16  

## Overview

This guide helps developers quickly get started writing and running API tests using the established patterns in this test suite.

## Prerequisites

- PHP 8.3+
- Composer dependencies installed
- PostgreSQL database configured
- Environment variables set in `.env.testing`

## Running Tests

### Run All API Tests
```bash
php artisan test tests/Feature/Api/
```

### Run Specific Test File
```bash
php artisan test tests/Feature/Api/V1/AuthenticationTest.php
```

### Run Tests in Parallel (faster)
```bash
php artisan test --parallel
```

### Run with Coverage
```bash
php artisan test --coverage --min=80
```

### Watch Mode (reruns on file change)
```bash
php artisan test --watch
```

## Writing Your First Test

### 1. Choose the Right Test File

Match the controller you're testing:
- Testing `AuthController`? → `AuthenticationTest.php`
- Testing `GameController`? → `GameLifecycleTest.php`
- New controller? → Create `{ControllerName}Test.php`

### 2. Use Describe Blocks for Organization

```php
<?php

use App\Models\Auth\User;

describe('Feature Domain', function () {
    describe('Specific Operation', function () {
        it('performs action successfully', function () {
            // Arrange
            $user = User::factory()->create();
            
            // Act
            $response = actingAs($user)
                ->postJson('/api/v1/endpoint', ['data' => 'value']);
            
            // Assert
            $response->assertOk()
                     ->assertJsonStructure(['id', 'created_at']);
        });
        
        it('rejects invalid input', function () {
            $user = User::factory()->create();
            
            $response = actingAs($user)
                ->postJson('/api/v1/endpoint', ['data' => '']);
            
            $response->assertUnprocessable()
                     ->assertJsonValidationErrors('data');
        });
    });
});
```

### 3. Use Factories for Test Data

```php
// Create basic user
$user = User::factory()->create();

// Create user with specific attributes
$user = User::factory()->create([
    'email' => 'test@example.com',
    'name' => 'Test User',
]);

// Use factory state methods
$user = User::factory()->verified()->create();
$subscription = Subscription::factory()->stripe()->active()->create();
$game = Game::factory()->inProgress()->create();
```

### 4. Test Authentication

```php
// Unauthenticated request
$response = $this->getJson('/api/v1/protected-endpoint');
$response->assertUnauthorized();

// Authenticated request
$user = User::factory()->create();
$response = actingAs($user)->getJson('/api/v1/protected-endpoint');
$response->assertOk();
```

### 5. Verify Database Changes

```php
it('creates subscription in database', function () {
    $user = User::factory()->create();
    
    actingAs($user)
        ->postJson('/api/v1/billing/subscribe', [
            'payment_method' => 'pm_card_visa',
        ])
        ->assertCreated();
    
    // Verify database state
    expect($user->subscriptions()->count())->toBe(1);
    expect($user->subscriptions()->first()->status)->toBe('active');
});
```

## Common Patterns

### Testing with Datasets

For testing multiple similar scenarios:

```php
it('verifies receipt for platform', function (string $platform) {
    $user = User::factory()->create();
    
    actingAs($user)
        ->postJson("/api/v1/billing/{$platform}/verify", [
            'receipt' => 'test-receipt',
        ])
        ->assertCreated();
})->with(['apple', 'google', 'telegram']);
```

### Testing Validation Errors

```php
it('validates required fields', function () {
    $user = User::factory()->create();
    
    actingAs($user)
        ->postJson('/api/v1/endpoint', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['field1', 'field2']);
});
```

### Testing Authorization

```php
it('prevents unauthorized access to other user data', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $game = Game::factory()->create();
    $game->players()->attach($user1->id, ['player_number' => 1]);
    
    // User2 tries to access User1's game
    actingAs($user2)
        ->getJson("/api/v1/games/{$game->ulid}")
        ->assertForbidden();
});
```

### Testing Events/Broadcasting

```php
use Illuminate\Support\Facades\Event;
use App\Events\GameActionProcessed;

it('broadcasts game update event', function () {
    Event::fake([GameActionProcessed::class]);
    
    $user = User::factory()->create();
    $game = createActiveGame($user);
    
    actingAs($user)
        ->postJson("/api/v1/games/{$game->ulid}/action", [
            'action_type' => 'DROP_PIECE',
            'column' => 3,
        ])
        ->assertOk();
    
    Event::assertDispatched(GameActionProcessed::class);
});
```

### Testing Rate Limits

```php
it('enforces rate limit', function () {
    $user = User::factory()->create();
    
    // Make requests up to limit
    for ($i = 0; $i < 5; $i++) {
        actingAs($user)->postJson('/api/v1/endpoint', [])->assertOk();
    }
    
    // Next request should be rate limited
    actingAs($user)
        ->postJson('/api/v1/endpoint', [])
        ->assertTooManyRequests();
});
```

## Using Helper Functions

### Global Helpers (defined in tests/Pest.php)

```php
// Create and authenticate user in one call
$user = createAuthenticatedUser(['name' => 'Test User']);

// Assert validation error with custom helper
assertValidationError($response, 'email');

// Assert API error structure
assertApiError($response, 422, 'Validation failed');
```

### Test Traits (for complex setups)

```php
use Tests\Feature\Traits\CreatesGames;
use Tests\Feature\Traits\CreatesSubscriptions;

describe('Complex Feature', function () {
    // Use trait methods
    it('tests game with subscription', function () {
        $user = User::factory()->create();
        $subscription = $this->createStripeSubscription($user);
        $game = $this->createActiveGame($user, 'validate-four');
        
        // Test logic...
    });
})->uses(CreatesGames::class, CreatesSubscriptions::class);
```

## Custom Expectations

Make assertions more readable:

```php
$response = actingAs($user)->getJson('/api/v1/auth/user');

// Instead of multiple assertJsonPath calls
expect($response)->toHaveUserStructure();

// Instead of multiple game state checks
expect($response->json('game'))->toHaveGameStructure();

// Fluent API response check
expect($response)->toBeSuccessfulApiResponse();
```

## Debugging Tests

### See Full Response
```php
$response = actingAs($user)->postJson('/api/v1/endpoint', []);
dd($response->json()); // Dump and die
```

### Check Database State
```php
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('games', ['ulid' => $game->ulid]);
```

### Debug Validation Errors
```php
$response->dump(); // Outputs response to terminal
$response->dumpHeaders();
$response->dumpSession();
```

### Run Single Test
```php
php artisan test --filter="performs action successfully"
```

## Best Practices

### ✅ Do

- Use factories for all test data
- Use describe blocks for organization
- Test both success and error cases
- Keep tests focused (one assertion per test)
- Use meaningful test names describing behavior
- Clean up test data (RefreshDatabase handles this)
- Mock external API calls

### ❌ Don't

- Hardcode IDs or ULIDs (use factories)
- Test multiple scenarios in one test
- Rely on test execution order
- Use sleep() or delays (use time travel)
- Make actual external API calls
- Share state between tests

## Troubleshooting

### Tests Fail Locally But Pass in CI

**Problem**: Database state differences  
**Solution**: Ensure `.env.testing` matches CI configuration

### Tests Are Slow

**Problem**: Not using database transactions  
**Solution**: Verify `RefreshDatabase` trait is used in `tests/Pest.php`

### Validation Errors Not Caught

**Problem**: Using wrong assertion method  
**Solution**: Use `assertJsonValidationErrors('field')` not `assertJsonPath()`

### Factory Relationship Errors

**Problem**: Missing pivot data  
**Solution**: Explicitly attach relationships with attributes:
```php
$game->players()->attach($user->id, ['player_number' => 1]);
```

## Next Steps

1. Read [test-organization.md](./contracts/test-organization.md) for complete test file structure
2. Review [data-model.md](./data-model.md) for entity relationships
3. Check [research.md](./research.md) for technical decisions
4. Start writing tests following the patterns above

## Getting Help

- Review existing tests in `tests/Feature/Api/V1/`
- Check Pest documentation: https://pestphp.com/
- Check Laravel testing docs: https://laravel.com/docs/12.x/testing

## Example Complete Test File

```php
<?php

use App\Models\Auth\User;
use App\Models\Game\Game;
use Illuminate\Support\Facades\Event;

describe('Game Actions', function () {
    describe('Submit Action', function () {
        it('accepts valid move and updates game state', function () {
            $user = User::factory()->create();
            $game = Game::factory()->inProgress()->create();
            $game->players()->attach($user->id, ['player_number' => 1]);
            
            $response = actingAs($user)
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'DROP_PIECE',
                    'column' => 3,
                ]);
            
            $response->assertOk()
                     ->assertJsonStructure([
                         'action' => ['id', 'action_type', 'created_at'],
                         'game' => ['ulid', 'status', 'current_turn'],
                     ]);
            
            $game->refresh();
            expect($game->actions()->count())->toBe(1);
            expect($game->current_turn)->toBe(2);
        });
        
        it('rejects invalid column', function () {
            $user = User::factory()->create();
            $game = Game::factory()->inProgress()->create();
            $game->players()->attach($user->id, ['player_number' => 1]);
            
            actingAs($user)
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'DROP_PIECE',
                    'column' => 99, // Invalid
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('column');
        });
        
        it('rejects action when not player turn', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $game = Game::factory()->inProgress()->create(['current_turn' => 1]);
            $game->players()->attach($user1->id, ['player_number' => 1]);
            $game->players()->attach($user2->id, ['player_number' => 2]);
            
            // User2 tries to move on User1's turn
            actingAs($user2)
                ->postJson("/api/v1/games/{$game->ulid}/action", [
                    'action_type' => 'DROP_PIECE',
                    'column' => 3,
                ])
                ->assertForbidden()
                ->assertJson(['message' => 'Not your turn']);
        });
    });
});
```

This quickstart provides everything needed to start writing consistent, maintainable API tests for the protocol project.
