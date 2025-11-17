# Test Organization Contract

**Feature**: 005-api-test-suite  
**Date**: 2025-01-16  

## Overview

This document defines the contract for how tests are organized, grouped, and structured across all test files. It ensures consistency and maintainability across the 40+ API endpoints being tested.

## Test File Organization

### Naming Convention
- One test file per controller/feature domain
- File names match controller names: `{ControllerName}Test.php`
- Location: `tests/Feature/Api/V1/`

### Grouping Structure
Each test file uses Pest's `describe()` blocks for hierarchical organization:

```php
describe('Feature Domain', function () {
    describe('Specific Endpoint/Operation', function () {
        describe('Scenario Category', function () {
            it('performs specific action', function () { /* ... */ });
        });
    });
});
```

## Test File Contracts

### 1. AuthenticationTest.php

**Endpoints Covered**:
- POST `/api/v1/auth/register`
- POST `/api/v1/auth/verify`
- POST `/api/v1/auth/login`
- POST `/api/v1/auth/social`
- POST `/api/v1/auth/logout`
- GET `/api/v1/auth/user`
- PATCH `/api/v1/auth/user`

**Test Groups**:
```php
describe('Authentication', function () {
    describe('Registration', function () {
        describe('Valid Input', function () {
            it('creates user and returns token');
            it('sends verification email');
        });
        
        describe('Invalid Input', function () {
            it('rejects duplicate email');
            it('rejects invalid email format');
            it('rejects weak password');
        });
    });
    
    describe('Email Verification', function () {
        it('verifies email with valid token');
        it('rejects invalid token');
        it('rejects expired token');
    });
    
    describe('Login', function () {
        it('returns token with valid credentials');
        it('rejects invalid password');
        it('rejects unverified email');
        it('enforces rate limiting after 5 attempts');
    });
    
    describe('Social Login', function () {
        it('creates user from OAuth provider')->with(['google', 'facebook', 'github']);
        it('links existing user account');
        it('rejects invalid OAuth token');
    });
    
    describe('Token Management', function () {
        it('returns current user with valid token');
        it('updates user profile');
        it('logs out and revokes tokens');
        it('rejects expired token');
    });
});
```

**Expected Tests**: ~15-20 tests

---

### 2. GameLifecycleTest.php

**Endpoints Covered**:
- GET `/api/v1/games`
- GET `/api/v1/games/{ulid}`
- POST `/api/v1/games/{ulid}/action`
- GET `/api/v1/games/{ulid}/options`

**Test Groups**:
```php
describe('Game Lifecycle', function () {
    describe('Game Retrieval', function () {
        it('lists user games with pagination');
        it('shows single game details');
        it('rejects unauthorized access to other user game');
    });
    
    describe('Game Actions', function () {
        describe('Valid Moves', function () {
            it('accepts valid DROP_PIECE action');
            it('updates game state correctly');
            it('advances turn to next player');
            it('detects win condition');
            it('broadcasts game update event');
        });
        
        describe('Invalid Moves', function () {
            it('rejects action when not player turn');
            it('rejects invalid column for DROP_PIECE');
            it('rejects action in completed game');
            it('rejects action by non-player');
        });
    });
    
    describe('Valid Options', function () {
        it('returns available moves for current player');
        it('returns empty array when not player turn');
    });
});
```

**Expected Tests**: ~12-15 tests

---

### 3. QuickplayTest.php

**Endpoints Covered**:
- POST `/api/v1/games/quickplay`
- DELETE `/api/v1/games/quickplay`
- POST `/api/v1/games/quickplay/accept`

**Test Groups**:
```php
describe('Quickplay Matchmaking', function () {
    describe('Join Queue', function () {
        it('adds user to matchmaking queue');
        it('matches two users immediately');
        it('returns match_id when game created');
        it('enforces quota limits');
        it('rejects suspended subscriptions');
    });
    
    describe('Leave Queue', function () {
        it('removes user from queue');
        it('returns 404 when not in queue');
    });
    
    describe('Accept Match', function () {
        it('confirms user acceptance');
        it('starts game when all players accept');
        it('rejects expired match');
    });
});
```

**Expected Tests**: ~8-10 tests

---

### 4. LobbyTest.php

**Endpoints Covered**:
- GET `/api/v1/games/lobbies`
- POST `/api/v1/games/lobbies`
- GET `/api/v1/games/lobbies/{ulid}`
- DELETE `/api/v1/games/lobbies/{ulid}`
- POST `/api/v1/games/lobbies/{ulid}/ready-check`

**Test Groups**:
```php
describe('Lobby Management', function () {
    describe('Lobby Creation', function () {
        it('creates lobby with valid mode');
        it('generates unique join code');
        it('sets creator as host');
    });
    
    describe('Lobby Listing', function () {
        it('shows user active lobbies');
        it('filters by status');
    });
    
    describe('Ready Check', function () {
        it('marks all players as ready');
        it('creates game when all ready');
        it('only host can start ready check');
    });
    
    describe('Lobby Deletion', function () {
        it('cancels lobby by host');
        it('rejects deletion by non-host');
    });
});
```

**Expected Tests**: ~10-12 tests

---

### 5. LobbyPlayerTest.php

**Endpoints Covered**:
- POST `/api/v1/games/lobbies/{ulid}/players`
- PUT `/api/v1/games/lobbies/{ulid}/players/{user_id}`
- DELETE `/api/v1/games/lobbies/{ulid}/players/{user_id}`

**Test Groups**:
```php
describe('Lobby Players', function () {
    describe('Player Invitation', function () {
        it('invites user to lobby');
        it('sends alert notification');
        it('rejects duplicate invitation');
        it('rejects when lobby full');
    });
    
    describe('Invitation Response', function () {
        it('accepts invitation');
        it('declines invitation');
        it('rejects expired invitation');
    });
    
    describe('Player Removal', function () {
        it('host kicks player');
        it('player leaves voluntarily');
        it('rejects kick by non-host');
    });
});
```

**Expected Tests**: ~8-10 tests

---

### 6. BillingTest.php

**Endpoints Covered**:
- GET `/api/v1/billing/plans`
- GET `/api/v1/billing/status`
- POST `/api/v1/billing/subscribe`
- GET `/api/v1/billing/manage`
- POST `/api/v1/billing/apple/verify`
- POST `/api/v1/billing/google/verify`
- POST `/api/v1/billing/telegram/verify`

**Test Groups**:
```php
describe('Billing', function () {
    describe('Plans & Status', function () {
        it('lists available subscription plans');
        it('shows current subscription status');
        it('shows quota and usage');
    });
    
    describe('Stripe Subscription', function () {
        it('creates subscription with valid payment method');
        it('creates customer if not exists');
        it('applies trial period');
        it('returns checkout session URL');
    });
    
    describe('IAP Verification', function () {
        it('verifies receipt from platform')->with(['apple', 'google', 'telegram']);
        it('creates subscription on first purchase');
        it('updates subscription on renewal');
        it('handles receipt validation failure');
    });
    
    describe('Subscription Management', function () {
        it('returns Stripe portal URL');
        it('requires active Stripe subscription');
    });
});
```

**Expected Tests**: ~12-15 tests

---

### 7. ProfileTest.php

**Endpoints Covered**:
- GET `/api/v1/me/profile`
- PATCH `/api/v1/me/profile`

**Test Groups**:
```php
describe('User Profile', function () {
    describe('Profile Retrieval', function () {
        it('shows current user profile');
        it('includes avatar and stats');
    });
    
    describe('Profile Update', function () {
        it('updates name');
        it('updates avatar URL');
        it('rejects invalid avatar URL');
        it('sanitizes input data');
    });
});
```

**Expected Tests**: ~5-7 tests

---

### 8. UserStatsTest.php

**Endpoints Covered**:
- GET `/api/v1/me/stats`

**Test Groups**:
```php
describe('User Stats', function () {
    it('shows aggregated game statistics');
    it('includes wins, losses, draws');
    it('includes per-title breakdown');
    it('calculates win rate correctly');
});
```

**Expected Tests**: ~4-5 tests

---

### 9. UserLevelsTest.php

**Endpoints Covered**:
- GET `/api/v1/me/levels`

**Test Groups**:
```php
describe('User Levels', function () {
    it('shows levels for all game titles');
    it('includes current XP and next level threshold');
    it('shows progression percentage');
});
```

**Expected Tests**: ~3-4 tests

---

### 10. AlertTest.php

**Endpoints Covered**:
- GET `/api/v1/me/alerts`
- POST `/api/v1/me/alerts/mark-as-read`

**Test Groups**:
```php
describe('Alerts', function () {
    describe('Alert Listing', function () {
        it('shows unread alerts');
        it('paginates alerts');
        it('filters by type');
    });
    
    describe('Mark as Read', function () {
        it('marks single alert as read');
        it('marks multiple alerts as read');
        it('rejects invalid alert ID');
    });
});
```

**Expected Tests**: ~6-8 tests

---

### 11. RematchTest.php

**Endpoints Covered**:
- POST `/api/v1/games/rematch/{id}/accept`
- POST `/api/v1/games/rematch/{id}/decline`

**Test Groups**:
```php
describe('Rematch Requests', function () {
    describe('Accept Rematch', function () {
        it('creates new game');
        it('swaps player positions');
        it('notifies requester');
    });
    
    describe('Decline Rematch', function () {
        it('updates request status');
        it('notifies requester');
    });
    
    describe('Edge Cases', function () {
        it('rejects expired rematch');
        it('rejects already responded rematch');
    });
});
```

**Expected Tests**: ~6-8 tests

---

### 12. PublicEndpointsTest.php

**Endpoints Covered**:
- GET `/api/v1/status`
- GET `/api/v1/titles`
- GET `/api/v1/titles/{title}/rules`
- GET `/api/v1/leaderboard/{title}`

**Test Groups**:
```php
describe('Public Endpoints', function () {
    describe('System Status', function () {
        it('returns API health status');
        it('does not require authentication');
    });
    
    describe('Game Titles', function () {
        it('lists all available game titles');
        it('includes mode information');
    });
    
    describe('Game Rules', function () {
        it('shows rules for specific title');
        it('returns 404 for invalid title');
    });
    
    describe('Leaderboards', function () {
        it('shows top players for title');
        it('paginates results');
        it('filters by time period');
    });
});
```

**Expected Tests**: ~8-10 tests

---

### 13. StripeWebhookTest.php

**Endpoints Covered**:
- POST `/api/v1/stripe/webhook`

**Test Groups**:
```php
describe('Stripe Webhooks', function () {
    describe('Signature Verification', function () {
        it('processes webhook with valid signature');
        it('rejects webhook with invalid signature');
    });
    
    describe('Event Handling', function () {
        it('handles customer.subscription.created');
        it('handles customer.subscription.updated');
        it('handles customer.subscription.deleted');
        it('handles invoice.payment_succeeded');
        it('handles invoice.payment_failed');
    });
    
    describe('Idempotency', function () {
        it('ignores duplicate webhook events');
    });
});
```

**Expected Tests**: ~8-10 tests

---

## Shared Test Helpers

### Global Functions (tests/Pest.php)

```php
/**
 * Create and authenticate a user for API requests
 */
function createAuthenticatedUser(array $attributes = []): User;

/**
 * Assert validation error in JSON response
 */
function assertValidationError(TestResponse $response, string $field): void;

/**
 * Assert standard JSON API error structure
 */
function assertApiError(TestResponse $response, int $status, string $message): void;
```

### Test Traits (tests/Feature/Traits/)

```php
trait CreatesGames {
    protected function createActiveGame(User $user, string $title = 'validate-four'): Game;
}

trait CreatesSubscriptions {
    protected function createStripeSubscription(User $user, string $status = 'active'): Subscription;
    protected function createAppleSubscription(User $user): Subscription;
}

trait InteractsWithWebSocket {
    protected function assertEventBroadcast(string $eventClass): void;
}
```

### Custom Expectations (tests/Pest.php)

```php
expect()->extend('toHaveUserStructure', function () { /* ... */ });
expect()->extend('toHaveGameStructure', function () { /* ... */ });
expect()->extend('toHaveSubscriptionStructure', function () { /* ... */ });
expect()->extend('toBeSuccessfulApiResponse', function () { /* ... */ });
```

## Test Execution Order

Tests are independent and can run in any order. Database transactions ensure isolation.

**Recommended CI execution**:
```bash
php artisan test --parallel --coverage
```

**Grouped execution for debugging**:
```bash
php artisan test tests/Feature/Api/V1/AuthenticationTest.php
php artisan test tests/Feature/Api/V1/GameLifecycleTest.php
# etc...
```

## Coverage Requirements

Each endpoint must have:
1. **Happy path test**: Valid input produces expected 200/201 response
2. **Validation test**: Invalid input produces 422 with error details
3. **Authorization test**: Unauthorized access produces 401/403
4. **Edge case tests**: Boundary conditions documented in spec

**Minimum 2 tests per endpoint** (happy + error path)

## Summary

Total test files: 13  
Total estimated tests: 100-120  
Expected execution time: <30 seconds  
Coverage: 100% of API endpoints in routes/api.php
