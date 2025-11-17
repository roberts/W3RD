# Research: API Test Suite

**Feature**: 005-api-test-suite  
**Date**: 2025-01-16  
**Status**: Complete

## Overview

This document consolidates research findings for implementing a comprehensive API test suite using Pest v4. All technical decisions are based on existing project infrastructure (Laravel 12, Pest v4.1, PostgreSQL) and best practices for API testing.

## Research Tasks

### 1. Pest v4 Features for API Testing

**Decision**: Use Pest v4's describe() blocks, datasets, and higher-order testing features

**Rationale**:
- `describe()` blocks provide nested test organization matching spec requirements for "grouped descriptions"
- Datasets enable parameterized testing for multiple platforms (Stripe, Apple, Google, Telegram) without duplication
- Higher-order expectations (`->toBeSuccessful()`, `->toBeJson()`) reduce boilerplate
- Architectural testing plugin can verify API contract consistency

**Alternatives Considered**:
- Traditional PHPUnit: Rejected due to more verbose syntax and lack of describe() organization
- Custom test grouping via traits: Rejected as Pest's native features are more maintainable

**Implementation Notes**:
```php
// Example structure using describe()
describe('Authentication', function () {
    describe('Registration', function () {
        it('creates user with valid data', function () { /* ... */ });
        it('rejects invalid email', function () { /* ... */ });
    });
    
    describe('Login', function () {
        it('returns token with valid credentials', function () { /* ... */ })->with('credentials');
    });
});
```

### 2. DRY Principles in Laravel Testing

**Decision**: Implement three-tier helper system: Global helpers (Pest.php), Test Traits, and Helper classes

**Rationale**:
- **Global helpers in Pest.php**: Authentication context switching (`actingAs()` already exists)
- **Test Traits**: Reusable setup/teardown for complex scenarios (CreatesGames, CreatesSubscriptions)
- **Helper classes**: Stateless utility functions for assertions and data generation

**Alternatives Considered**:
- Single helper file: Rejected due to poor organization for 40+ endpoints
- Base test class methods: Rejected as traits provide better composition

**Implementation Notes**:
```php
// tests/Pest.php additions
function createAuthenticatedUser(array $attributes = []): User {
    return User::factory()->create($attributes);
}

function assertValidationError($response, string $field): void {
    $response->assertStatus(422)
             ->assertJsonValidationErrors($field);
}

// tests/Feature/Traits/CreatesGames.php
trait CreatesGames {
    protected function createActiveGame(User $user, string $title = 'validate-four'): Game {
        $game = Game::factory()->create(['title_slug' => $title]);
        $game->players()->attach($user->id, ['player_number' => 1]);
        return $game;
    }
}
```

### 3. Database State Management for Fast Tests

**Decision**: Use `RefreshDatabase` trait with database transactions per test

**Rationale**:
- Already configured in `tests/Pest.php`: `->use(Illuminate\Foundation\Testing\RefreshDatabase::class)`
- Laravel automatically wraps each test in a transaction and rolls back
- Migrations run once at start, then transactions provide isolation
- Achieves <30s target (current tests with 1 feature test run instantly)

**Alternatives Considered**:
- `DatabaseMigrations`: Rejected as slower (re-migrates per test)
- Manual cleanup: Rejected as error-prone and violates DRY
- In-memory SQLite: Rejected as PostgreSQL-specific features may behave differently

**Implementation Notes**:
- No changes needed to existing `tests/Pest.php` configuration
- All new tests will inherit `RefreshDatabase` via `pest()->in('Feature')` directive

### 4. Testing WebSocket/Real-time Features

**Decision**: Use Laravel Reverb's testing helpers with mock broadcasting

**Rationale**:
- Laravel Reverb v1.6 is already installed (`laravel/reverb: ^1.6` in composer.json)
- Laravel provides `Event::fake()` and `Broadcast::fake()` for testing events
- Can assert events were dispatched without running actual WebSocket server
- Tests remain fast and don't require external services

**Alternatives Considered**:
- Actual WebSocket connections: Rejected as too slow and flaky for unit tests
- Separate integration test suite: Rejected as adds complexity for minimal benefit

**Implementation Notes**:
```php
use Illuminate\Support\Facades\Event;

it('broadcasts game update when action submitted', function () {
    Event::fake([GameActionProcessed::class]);
    
    $game = createActiveGame($user = createAuthenticatedUser());
    
    actingAs($user)
        ->postJson("/api/v1/games/{$game->ulid}/action", [
            'action_type' => 'DROP_PIECE',
            'column' => 3,
        ])
        ->assertOk();
    
    Event::assertDispatched(GameActionProcessed::class);
});
```

### 5. Testing Multiple Billing Platforms

**Decision**: Use Pest datasets for platform-specific tests with mocked external APIs

**Rationale**:
- Datasets eliminate duplication across 4 platforms (Stripe, Apple, Google, Telegram)
- Each platform has different verification flow but similar test structure
- Mocking prevents actual API calls (Guzzle HTTP client already available)
- Platform-specific factories already created (SubscriptionFactory states)

**Alternatives Considered**:
- Separate test file per platform: Rejected as duplicates structure
- VCR/recorded responses: Rejected as adds dependency and complexity

**Implementation Notes**:
```php
it('verifies receipt for platform', function (string $platform) {
    $user = createAuthenticatedUser();
    
    // Mock external API responses
    Http::fake([
        "*.{$platform}.com/*" => Http::response(['status' => 'valid'], 200),
    ]);
    
    actingAs($user)
        ->postJson("/api/v1/billing/{$platform}/verify", [
            'receipt' => 'mock-receipt-data',
        ])
        ->assertCreated()
        ->assertJson(['platform' => $platform]);
})->with(['apple', 'google', 'telegram']);
```

### 6. JSON Response Structure Validation

**Decision**: Create custom `assertJsonStructure()` expectations for API contracts

**Rationale**:
- Laravel provides `assertJsonStructure()` but repetitive for consistent API responses
- Custom expectations in `tests/Pest.php` enforce standard response shapes
- Reduces 10-15 line assertions to single method call

**Alternatives Considered**:
- OpenAPI validation: Rejected as adds significant complexity
- JSON Schema validation: Rejected as overkill for internal API

**Implementation Notes**:
```php
// tests/Pest.php
expect()->extend('toHaveUserStructure', function () {
    return $this->assertJsonStructure([
        'id', 'name', 'email', 'avatar_url',
        'created_at', 'updated_at',
    ]);
});

expect()->extend('toHaveGameStructure', function () {
    return $this->assertJsonStructure([
        'ulid', 'title', 'status', 'current_turn',
        'players' => [['id', 'player_number']],
        'game_state', 'created_at',
    ]);
});
```

### 7. Rate Limiting Test Strategy

**Decision**: Use `Travel` facade to simulate time passage and verify 429 responses

**Rationale**:
- Laravel's `Travel` facade can manipulate time without waiting
- Rate limits defined in `config/rate-limiter.php` (assumed standard Laravel config)
- Tests remain fast while verifying rate limit enforcement

**Alternatives Considered**:
- Actual delayed requests: Rejected as tests would take minutes
- Disable rate limiting in tests: Rejected as defeats purpose of testing

**Implementation Notes**:
```php
use Illuminate\Support\Facades\RateLimiter;

it('enforces rate limit on login attempts', function () {
    $user = User::factory()->create();
    
    // Make requests up to limit
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertUnprocessable();
    }
    
    // Next request should be rate limited
    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertStatus(429);
});
```

### 8. Test Execution Performance

**Decision**: No special configuration needed; rely on database transactions and factory optimization

**Rationale**:
- Current infrastructure already fast (single DashboardTest runs instantly)
- 40+ endpoint tests with simple CRUD operations should complete in <30s
- Database transactions provide fast isolation
- Can parallelize with Pest's `--parallel` flag if needed later

**Alternatives Considered**:
- In-memory database: Rejected as PostgreSQL-specific features may differ
- Test subset execution: Not needed yet, can add `@group` tags later if needed

**Implementation Notes**:
- Monitor test suite time as tests are added
- If approaching 30s limit, consider:
  - Adding `--parallel` flag to CI pipeline
  - Grouping tests with tags for selective execution
  - Optimizing factory queries with `createQuietly()` to skip events

## Summary

All research tasks complete with clear implementation paths. Key decisions:
1. Use Pest v4 describe() blocks for organization
2. Three-tier helper system (global functions, traits, helper classes)
3. RefreshDatabase with transactions (existing setup)
4. Mock broadcasting for real-time tests
5. Datasets for multi-platform billing tests
6. Custom expectations for JSON structure validation
7. Time travel for rate limit testing
8. No special performance tuning needed initially

Zero NEEDS CLARIFICATION markers - all technical details resolved from existing codebase and best practices.
