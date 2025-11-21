# Data Model: API Test Suite

**Feature**: 005-api-test-suite  
**Date**: 2025-01-16  
**Status**: Complete

## Overview

This document defines the test data structures and relationships needed for comprehensive API testing. All entities listed are existing models in the application - this feature does not create new database entities but documents how they're used in tests.

## Test Data Entities

### User
**Purpose**: Core authentication entity for all protected endpoints

**Attributes**:
- `id`: bigint (primary key)
- `ulid`: string (26 chars, unique identifier)
- `name`: string
- `email`: string (unique)
- `email_verified_at`: timestamp (nullable)
- `password`: hashed string
- `avatar_url`: string (nullable)
- `remember_token`: string (nullable)
- `created_at`, `updated_at`: timestamps

**Test Variations**:
- Unverified user (`email_verified_at` = null)
- Verified user (`email_verified_at` set)
- User with subscription (via relationship)
- User with quota exhausted (via Quota relationship)
- User with max strikes (via Strike relationship)

**Factory State Methods**: Already exists in `database/factories/Auth/UserFactory.php`

**Validation Rules from Requirements**:
- FR-001: Required for authentication tests
- FR-010: Required for protected endpoint tests
- FR-011: Required for authorization tests

### Game
**Purpose**: Primary entity for game lifecycle testing

**Attributes**:
- `id`: bigint (primary key)
- `ulid`: string (26 chars)
- `title_slug`: string (references game modes)
- `status`: enum (waiting, in_progress, completed, forfeited)
- `current_turn`: integer
- `winner_id`: bigint (nullable, foreign key to users)
- `game_state`: json (board state, game-specific data)
- `started_at`, `completed_at`: timestamps (nullable)
- `created_at`, `updated_at`: timestamps

**Relationships**:
- `players`: belongsToMany User (via game_player pivot)
- `actions`: hasMany Action
- `mode`: belongsTo GameMode
- `rematchRequests`: hasMany RematchRequest

**Test Variations**:
- Waiting game (status = waiting)
- Active game (status = in_progress)
- Completed game (status = completed, winner_id set)
- Game with multiple turns (actions submitted)

**Factory State Methods**: Already exists in `database/factories/Game/GameFactory.php`

**Validation Rules from Requirements**:
- FR-002: Complete game lifecycle testing
- FR-015: Database state changes verification

### Player
**Purpose**: Junction entity linking users to games with player-specific state

**Attributes**:
- `id`: bigint (primary key)
- `game_id`: foreign key
- `user_id`: foreign key
- `player_number`: integer (1, 2, etc.)
- `is_ready`: boolean
- `joined_at`, `left_at`: timestamps (nullable)
- `created_at`, `updated_at`: timestamps

**Factory State Methods**: Already exists in `database/factories/Game/PlayerFactory.php`

### Action
**Purpose**: Game moves/actions for testing game progression

**Attributes**:
- `id`: bigint (primary key)
- `game_id`: foreign key
- `player_id`: foreign key
- `action_type`: enum (DROP_PIECE, MOVE_PIECE, PLAY_CARD, PASS, DRAW_CARD, BID)
- `action_details`: json (column, row, card_id, etc.)
- `is_valid`: boolean
- `validation_error`: text (nullable)
- `created_at`: timestamp

**Test Variations**:
- Valid move (is_valid = true)
- Invalid move (is_valid = false, validation_error set)
- Different action types per game title

**Factory State Methods**: Already exists in `database/factories/Game/ActionFactory.php`

**Validation Rules from Requirements**:
- FR-002: Action submission and validation
- FR-009: Error handling for invalid inputs

### Subscription
**Purpose**: Billing platform testing across Stripe, Apple, Google, Telegram

**Attributes**:
- `id`: bigint (primary key)
- `user_id`: foreign key
- `platform`: enum (stripe, apple, google, telegram, admin)
- `stripe_subscription_id`: string (nullable)
- `stripe_customer_id`: string (nullable)
- `apple_transaction_id`: string (nullable)
- `google_purchase_token`: string (nullable)
- `telegram_payment_id`: string (nullable)
- `status`: enum (active, trialing, canceled, past_due, paused)
- `trial_ends_at`, `ends_at`: timestamps (nullable)
- `created_at`, `updated_at`: timestamps

**Relationships**:
- `user`: belongsTo User
- `quotas`: hasMany Quota
- `strikes`: hasMany Strike

**Test Variations**:
- Active Stripe subscription
- Trialing subscription
- Canceled subscription
- Platform-specific variations (apple, google, telegram)

**Factory State Methods**: Already exists in `database/factories/Billing/SubscriptionFactory.php` with platform states

**Validation Rules from Requirements**:
- FR-003: Multi-platform billing operations
- FR-015: Subscription activation state changes

### Quota
**Purpose**: Game limit enforcement testing

**Attributes**:
- `id`: bigint (primary key)
- `user_id`: foreign key
- `subscription_id`: foreign key (nullable)
- `games_started`: integer
- `games_allowed`: integer
- `reset_month`: string (YYYY-MM format)
- `created_at`, `updated_at`: timestamps

**Test Variations**:
- Quota available (games_started < games_allowed)
- Quota exhausted (games_started >= games_allowed)

**Factory State Methods**: Already exists in `database/factories/Billing/QuotaFactory.php` with atLimit state

**Validation Rules from Requirements**:
- FR-003: Quota enforcement testing

### Strike
**Purpose**: Penalty system testing for subscription violations

**Attributes**:
- `id`: bigint (primary key)
- `user_id`: foreign key
- `subscription_id`: foreign key
- `strike_count`: integer (0-3)
- `strike_date`: date (nullable)
- `reason`: text (nullable)
- `created_at`, `updated_at`: timestamps

**Test Variations**:
- No strikes (strike_count = 0)
- Warning strikes (strike_count = 1-2)
- Max strikes (strike_count = 3, suspended)

**Factory State Methods**: Already exists in `database/factories/Billing/StrikeFactory.php` with maxStrikes state

### Lobby
**Purpose**: Matchmaking and private game setup testing

**Attributes**:
- `id`: bigint (primary key)
- `ulid`: string (26 chars)
- `host_id`: foreign key (references users)
- `game_mode_id`: foreign key
- `status`: enum (waiting, ready, started, canceled)
- `join_code`: string (6 chars, unique)
- `max_players`: integer
- `is_private`: boolean
- `started_at`, `canceled_at`: timestamps (nullable)
- `created_at`, `updated_at`: timestamps

**Relationships**:
- `host`: belongsTo User
- `players`: hasMany LobbyPlayer
- `mode`: belongsTo GameMode

**Test Variations**:
- Waiting lobby (status = waiting)
- Ready lobby (all players ready)
- Started lobby (game created)

**Factory State Methods**: Already exists in `database/factories/Game/LobbyFactory.php`

**Validation Rules from Requirements**:
- FR-002: Complete game lifecycle (lobby to game)

### LobbyPlayer
**Purpose**: Lobby invitation and player management testing

**Attributes**:
- `id`: bigint (primary key)
- `lobby_id`: foreign key
- `user_id`: foreign key
- `status`: enum (invited, joined, ready, declined, kicked)
- `invited_at`, `joined_at`, `ready_at`: timestamps (nullable)
- `created_at`, `updated_at`: timestamps

**Test Variations**:
- Invited player (status = invited)
- Joined player (status = joined)
- Ready player (status = ready)
- Declined invitation (status = declined)

**Factory State Methods**: Already exists in `database/factories/Game/LobbyPlayerFactory.php`

### Alert
**Purpose**: Notification system testing

**Attributes**:
- `id`: bigint (primary key)
- `user_id`: foreign key
- `type`: enum (game_invite, game_started, game_completed, level_up, achievement_unlocked)
- `data`: json (game_id, lobby_id, etc.)
- `is_read`: boolean (default false)
- `read_at`: timestamp (nullable)
- `created_at`, `updated_at`: timestamps

**Test Variations**:
- Unread alert (is_read = false)
- Read alert (is_read = true, read_at set)
- Different alert types

**Factory State Methods**: Already exists in `database/factories/AlertFactory.php`

**Validation Rules from Requirements**:
- FR-002: Alert delivery testing

### RematchRequest
**Purpose**: Post-game rematch flow testing

**Attributes**:
- `id`: bigint (primary key)
- `original_game_id`: foreign key
- `requester_id`: foreign key (references users)
- `opponent_id`: foreign key (references users)
- `status`: enum (pending, accepted, declined, expired)
- `game_id`: foreign key (nullable)
- `expires_at`: timestamp
- `responded_at`: timestamp (nullable)
- `created_at`, `updated_at`: timestamps

**Test Variations**:
- Pending request (status = pending)
- Accepted request (status = accepted, game_id set)
- Declined request (status = declined)
- Expired request (status = expired)

**Factory State Methods**: Already exists in `database/factories/Game/RematchRequestFactory.php`

## Test Data Relationships

```text
User ──────────────┬──> Subscriptions (1:M)
                   ├──> Games (M:M via Players)
                   ├──> Lobbies as host (1:M)
                   ├──> LobbyPlayers (1:M)
                   ├──> Alerts (1:M)
                   ├──> Quotas (1:M)
                   ├──> Strikes (1:M)
                   └──> RematchRequests (1:M)

Game ──────────────┬──> Players (1:M)
                   ├──> Actions (1:M)
                   ├──> RematchRequests (1:M)
                   └──> GameMode (M:1)

Subscription ──────┬──> Quotas (1:M)
                   └──> Strikes (1:M)

Lobby ─────────────┬──> LobbyPlayers (1:M)
                   └──> GameMode (M:1)
```

## State Transitions

### Game Status Flow
```text
waiting → in_progress → completed
                     └→ forfeited
```

### Lobby Status Flow
```text
waiting → ready → started
              └→ canceled
```

### LobbyPlayer Status Flow
```text
invited → joined → ready
        └→ declined
```

### RematchRequest Status Flow
```text
pending → accepted (game_id set)
        ├→ declined
        └→ expired (after expires_at)
```

### Subscription Status Flow
```text
trialing → active → canceled
                 ├→ past_due
                 └→ paused
```

## Factory Usage Guidelines

All factories already exist in `database/factories/` with realistic test data and state methods. Test files should:

1. **Use state methods** for common variations:
   ```php
   User::factory()->verified()->create();
   Subscription::factory()->stripe()->active()->create();
   Game::factory()->inProgress()->create();
   ```

2. **Create relationships** explicitly for clarity:
   ```php
   $user = User::factory()->create();
   $game = Game::factory()->create();
   $game->players()->attach($user->id, ['player_number' => 1]);
   ```

3. **Override attributes** when testing specific scenarios:
   ```php
   Quota::factory()->create([
       'games_started' => 10,
       'games_allowed' => 10, // At limit
   ]);
   ```

## Validation State Coverage

Each test must verify:
- **Happy path**: Valid inputs produce expected state changes
- **Error path**: Invalid inputs produce expected error responses
- **Edge cases**: Boundary conditions documented in spec

Example state verifications:
```php
// After game completion
$game->refresh();
expect($game->status)->toBe(GameStatus::COMPLETED);
expect($game->winner_id)->toBe($user->id);
expect($game->completed_at)->not->toBeNull();

// After subscription creation
$user->refresh();
expect($user->subscriptions()->active()->count())->toBe(1);
expect($user->quotas()->first()->games_allowed)->toBe(100);
```

## Summary

All entities documented are existing models with factories already created. This feature leverages existing infrastructure to build comprehensive test coverage without introducing new data structures. Test data generation follows established factory patterns with state methods for common variations.
