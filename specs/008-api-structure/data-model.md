# Phase 1 Data Model: Production-Ready V1 API Structure

**Feature**: 008-api-structure | **Date**: November 20, 2025

## Overview

This document defines the data models and database schema for the new API namespaces. Most entities already exist; this feature primarily adds new entities for Floor coordination and Economy operations.

## Existing Models (Unchanged)

### User
*Already exists* - Central user account

**Fields**:
- `id`: bigint, primary key
- `username`: string, unique, indexed
- `email`: string, unique, indexed
- `email_verified_at`: timestamp, nullable
- `password`: string (hashed)
- `avatar_url`: string, nullable
- `bio`: text, nullable
- `total_xp`: integer, default 0
- `level`: integer, default 1
- `created_at`: timestamp
- `updated_at`: timestamp

**Relationships**:
- Has many Games (as player)
- Has many Alerts
- Has many Balances (one per client)
- Has many Transactions
- Has many MatchmakingSignals
- Has many Proposals (sent and received)
- Belongs to Client (via registration_client_id)

### Game
*Already exists* - Active game instance

**Fields**:
- `id`: bigint, primary key
- `ulid`: string(26), unique, indexed
- `title_id`: foreign key to titles
- `status`: enum(waiting, active, completed, abandoned)
- `state`: json (board, hands, etc.)
- `current_turn_user_id`: foreign key to users, nullable
- `winner_id`: foreign key to users, nullable
- `turn_expires_at`: timestamp, nullable
- `created_at`: timestamp
- `updated_at`: timestamp
- `completed_at`: timestamp, nullable

**Relationships**:
- Belongs to Title
- Belongs to many Users (players) through game_user pivot
- Has many GameActions
- Has many GameEvents

### Lobby
*Already exists* - Temporary coordination space

**Fields**:
- `id`: bigint, primary key
- `ulid`: string(26), unique, indexed
- `host_id`: foreign key to users
- `title_id`: foreign key to titles
- `visibility`: enum(public, private)
- `max_players`: integer
- `join_code`: string(6), unique, indexed, nullable
- `status`: enum(waiting, ready_check, starting, completed, cancelled)
- `created_at`: timestamp
- `updated_at`: timestamp

**Relationships**:
- Belongs to User (host)
- Belongs to Title
- Belongs to many Users (players) through lobby_user pivot

### Title
*Already exists* - Game definition

**Fields**:
- `id`: bigint, primary key
- `key`: string, unique, indexed (e.g., 'connect-four')
- `name`: string (e.g., 'Connect Four')
- `description`: text
- `player_count`: integer
- `pacing`: enum(real_time, turn_based)
- `complexity`: enum(simple, moderate, complex, expert)
- `category_tags`: json array
- `thumbnail_url`: string, nullable
- `average_session_minutes`: integer, nullable
- `rules_text`: text
- `rules_json`: json
- `entity_definitions`: json (cards, units, boards)
- `created_at`: timestamp
- `updated_at`: timestamp

### Alert
*Already exists* - User notification

**Fields**:
- `id`: bigint, primary key
- `ulid`: string(26), unique, indexed
- `user_id`: foreign key to users
- `type`: enum(game_invite, match_found, game_completed, friend_request, system)
- `title`: string
- `message`: text
- `data`: json (contextual data)
- `read_at`: timestamp, nullable
- `created_at`: timestamp

**Relationships**:
- Belongs to User

### Transaction
*Already exists* - Unified transaction record for both virtual balances and real payments

**Important Note**: This table handles two distinct transaction types:
1. **Virtual Balance Transactions** (entertainment only): Tokens/chips tracking for gameplay - no real money
2. **Real Payment Transactions**: Actual subscription payments via Stripe, Google Play, Apple Store, or Telegram

**Fields**:
- `id`: bigint, primary key
- `ulid`: string(26), unique, indexed
- `user_id`: foreign key to users
- `client_id`: foreign key to clients, nullable (required for balance transactions, optional for payments)
- `type`: enum - transaction category:
  - Virtual: `balance_add`, `balance_remove`, `game_buy_in`, `game_cash_out`
  - Subscription: `subscription_payment`, `subscription_refund`
  - IAP: `iap_purchase`, `iap_refund`
- `amount`: decimal(10,2)
- `currency`: enum(tokens, chips, usd), nullable
- `subscription_id`: foreign key to subscriptions, nullable (for subscription payments)
- `payment_provider`: enum(stripe, google_play, apple_store, telegram), nullable
- `provider_transaction_id`: string, nullable, indexed (Stripe payment ID, Google order ID, etc.)
- `payment_status`: enum(pending, completed, failed, refunded), nullable
- `reference`: string, nullable (client reference ID for balance operations)
- `metadata`: json
- `created_at`: timestamp
- `updated_at`: timestamp

**Indexes**:
- Index on (user_id, created_at) for transaction history
- Index on (client_id, created_at) for client reporting
- Index on (subscription_id, created_at) for subscription payment history
- Index on (type, created_at) for filtering by transaction type
- Index on reference for client reconciliation
- Index on provider_transaction_id for payment lookups

**Relationships**:
- Belongs to User
- Belongs to Client (optional - required for balance transactions)
- Belongs to Subscription (optional - for subscription-related payments)

**Relationship Benefits**:
- **subscription_id link**: Enables direct querying of all payments for a specific subscription
- **Audit trail**: Complete payment history attached to subscription records
- **Refund tracking**: Easy to find and match refunds to original payments
- **Reporting**: Subscription revenue and churn analysis simplified
- **Laravel Cashier integration**: Webhook handlers can create transaction records with subscription context

**Validation Rules**:
- All amounts must be > 0
- Virtual balance transactions (`balance_add`, `balance_remove`):
  - Must have client_id
  - Must have currency (tokens or chips)
  - Reference required for cashier operations
  - subscription_id should be null
- Subscription payment transactions:
  - Must have subscription_id
  - Must have payment_provider
  - Must have payment_status
  - Should have provider_transaction_id for tracking
  - Currency should be 'usd' (or other real currency)
- IAP transactions:
  - May have subscription_id (for subscription purchases)
  - Must have payment_provider
  - Must have payment_status
  - Should have provider_transaction_id

**Usage Patterns**:
```php
// Virtual balance transaction (no subscription)
Transaction::create([
    'ulid' => Str::ulid(),
    'user_id' => $user->id,
    'client_id' => $client->id,
    'type' => 'balance_add',
    'amount' => 100.00,
    'currency' => 'tokens',
    'reference' => 'client-ref-123',
]);

// Subscription payment via Stripe (linked to subscription)
Transaction::create([
    'ulid' => Str::ulid(),
    'user_id' => $user->id,
    'subscription_id' => $subscription->id,
    'type' => 'subscription_payment',
    'amount' => 9.99,
    'currency' => 'usd',
    'payment_provider' => 'stripe',
    'provider_transaction_id' => 'pi_1234567890',
    'payment_status' => 'completed',
]);

// Subscription refund (linked to subscription)
Transaction::create([
    'ulid' => Str::ulid(),
    'user_id' => $user->id,
    'subscription_id' => $subscription->id,
    'type' => 'subscription_refund',
    'amount' => 9.99,
    'currency' => 'usd',
    'payment_provider' => 'stripe',
    'provider_transaction_id' => 'pi_1234567890_refund',
    'payment_status' => 'completed',
]);

// Query all payments for a subscription
$payments = Transaction::where('subscription_id', $subscription->id)
    ->whereIn('type', ['subscription_payment', 'subscription_refund'])
    ->orderBy('created_at', 'desc')
    ->get();

// In-app purchase via Google Play (linked to subscription if recurring)
Transaction::create([
    'ulid' => Str::ulid(),
    'user_id' => $user->id,
    'subscription_id' => $subscription->id, // Link if it's a subscription purchase
    'type' => 'iap_purchase',
    'amount' => 4.99,
    'currency' => 'usd',
    'payment_provider' => 'google_play',
    'provider_transaction_id' => 'GPA.1234-5678-9012',
    'payment_status' => 'completed',
    'metadata' => ['product_id' => 'member_monthly'],
]);
```

### SubscriptionPlan
*Already exists* - Subscription tier definition

**Fields**:
- `id`: bigint, primary key
- `key`: string, unique (e.g., 'member', 'master')
- `name`: string (e.g., 'Member', 'Master')
- `description`: text
- `price`: decimal(10,2)
- `billing_cycle`: enum(monthly, yearly)
- `features`: json array
- `stripe_price_id`: string, nullable
- `active`: boolean, default true
- `created_at`: timestamp
- `updated_at`: timestamp

## New Models (To Be Created)

### MatchmakingSignal
*New* - User intent to play (quickplay/ranked)

**Fields**:
- `id`: bigint, primary key
- `ulid`: string(26), unique, indexed
- `user_id`: foreign key to users, indexed
- `title_id`: foreign key to titles, indexed
- `elo_rating`: integer (for matchmaking)
- `connection_quality`: enum(excellent, good, fair, poor)
- `preferred_pace`: enum(fast, normal, slow), nullable
- `expires_at`: timestamp (auto-cancel after 5 minutes)
- `created_at`: timestamp

**Indexes**:
- Composite index on (title_id, elo_rating, created_at) for matchmaking queries
- Index on (user_id, created_at) for user's active signals

**Relationships**:
- Belongs to User
- Belongs to Title

**Validation Rules**:
- User can only have one active signal at a time per title
- Signal automatically expires after 5 minutes
- ELO rating must be between 0 and 3000

**Migration**:
```php
Schema::create('matchmaking_signals', function (Blueprint $table) {
    $table->id();
    $table->string('ulid', 26)->unique()->index();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('title_id')->constrained()->onDelete('cascade');
    $table->integer('elo_rating')->default(1200);
    $table->enum('connection_quality', ['excellent', 'good', 'fair', 'poor'])->default('good');
    $table->enum('preferred_pace', ['fast', 'normal', 'slow'])->nullable();
    $table->timestamp('expires_at');
    $table->timestamp('created_at');
    
    $table->index(['title_id', 'elo_rating', 'created_at']);
    $table->index(['user_id', 'created_at']);
});
```

### Proposal
*New* - Direct challenge or rematch offer

**Fields**:
- `id`: bigint, primary key
- `ulid`: string(26), unique, indexed
- `type`: enum(challenge, rematch)
- `sender_id`: foreign key to users, indexed
- `recipient_id`: foreign key to users, indexed
- `title_id`: foreign key to titles
- `game_settings`: json (stake amount, time control, etc.)
- `previous_game_id`: foreign key to games, nullable (for rematches)
- `status`: enum(pending, accepted, declined, expired, cancelled)
- `expires_at`: timestamp (auto-decline after 5 minutes)
- `responded_at`: timestamp, nullable
- `created_at`: timestamp
- `updated_at`: timestamp

**Indexes**:
- Index on (recipient_id, status, created_at) for pending proposals
- Index on (sender_id, created_at) for sent proposals

**Relationships**:
- Belongs to User (sender)
- Belongs to User (recipient)
- Belongs to Title
- Belongs to Game (previous_game), nullable

**Validation Rules**:
- Cannot send proposal to self
- User can only have 5 pending outgoing proposals at a time
- Proposal automatically expires after 5 minutes if not responded to
- Rematch proposals must reference a completed game

**Migration**:
```php
Schema::create('proposals', function (Blueprint $table) {
    $table->id();
    $table->string('ulid', 26)->unique()->index();
    $table->enum('type', ['challenge', 'rematch']);
    $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('title_id')->constrained()->onDelete('cascade');
    $table->json('game_settings')->nullable();
    $table->foreignId('previous_game_id')->nullable()->constrained('games')->onDelete('set null');
    $table->enum('status', ['pending', 'accepted', 'declined', 'expired', 'cancelled'])->default('pending');
    $table->timestamp('expires_at');
    $table->timestamp('responded_at')->nullable();
    $table->timestamps();
    
    $table->index(['recipient_id', 'status', 'created_at']);
    $table->index(['sender_id', 'created_at']);
});
```

### Balance
*New* - User's virtual currency holdings per client (entertainment only)

**Important Note**: This entity tracks virtual tokens and chips for entertainment purposes only. No real money or cryptocurrency transactions occur. Approved client applications manage user balances through the cashier service.

**Multi-Client Architecture**: Each user can have separate balances for different client applications. This enables:
- Client-specific virtual economies
- Isolated balance tracking per client
- Client-specific chips only used in games where all players use that client
- Cross-client token transfers (if implemented)

**Fields**:
- `id`: bigint, primary key
- `user_id`: foreign key to users, indexed
- `client_id`: foreign key to clients, indexed
- `tokens`: decimal(10,2), default 0.00 (virtual currency for gameplay)
- `chips`: decimal(10,2), default 0.00 (virtual currency for gameplay)
- `locked_in_games`: decimal(10,2), default 0.00 (currently in active games)
- `updated_at`: timestamp

**Indexes**:
- Unique composite index on (user_id, client_id) - one balance per user per client
- Index on client_id for client-wide queries

**Relationships**:
- Belongs to User
- Belongs to Client

**Validation Rules**:
- All balance fields must be >= 0
- Virtual tokens and chips are for entertainment only
- Locked amounts represent virtual currency in active gameplay
- Unique constraint ensures one balance record per user-client combination

**Game Buy-in Logic**:
- Chips can only be used in games where all players are using the same client
- System checks client_id match across all players before allowing chip buy-ins
- Tokens may have different rules (potentially cross-client)

**Migration**:
```php
Schema::create('balances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('client_id')->constrained()->onDelete('cascade');
    $table->decimal('tokens', 10, 2)->default(0.00)->comment('Virtual tokens for gameplay');
    $table->decimal('chips', 10, 2)->default(0.00)->comment('Virtual chips for gameplay');
    $table->decimal('locked_in_games', 10, 2)->default(0.00)->comment('Virtual currency in active games');
    $table->timestamp('updated_at');

    // Unique constraint: one balance per user per client
    $table->unique(['user_id', 'client_id']);
    
    // Index for client balance queries
    $table->index('client_id');

    // Ensure balances cannot go negative
    $table->check('tokens >= 0');
    $table->check('chips >= 0');
    $table->check('locked_in_games >= 0');
});
```

### Tournament
*New* - Structured competition

**Fields**:
- `id`: bigint, primary key
- `ulid`: string(26), unique, indexed
- `title_id`: foreign key to titles
- `name`: string
- `description`: text, nullable
- `format`: enum(single_elimination, double_elimination, round_robin, swiss)
- `buy_in_amount`: decimal(10,2)
- `buy_in_currency`: enum(real_money, bonus_chips, hard_currency)
- `prize_pool`: decimal(10,2)
- `prize_distribution`: json (1st: 50%, 2nd: 30%, etc.)
- `max_participants`: integer
- `min_participants`: integer
- `status`: enum(scheduled, registration_open, in_progress, completed, cancelled)
- `phase_rules`: json (blind levels, time limits, etc.)
- `starts_at`: timestamp
- `ends_at`: timestamp, nullable
- `created_at`: timestamp
- `updated_at`: timestamp

**Indexes**:
- Index on (status, starts_at) for active tournaments
- Index on (title_id, status) for game-specific tournaments

**Relationships**:
- Belongs to Title
- Belongs to many Users (participants) through tournament_user pivot
- Has many Games (tournament matches)

**Validation Rules**:
- starts_at must be in the future when creating
- max_participants must be >= min_participants
- buy_in_amount must be > 0
- prize_pool must be >= total buy-ins

**Migration**:
```php
Schema::create('tournaments', function (Blueprint $table) {
    $table->id();
    $table->string('ulid', 26)->unique()->index();
    $table->foreignId('title_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->text('description')->nullable();
    $table->enum('format', ['single_elimination', 'double_elimination', 'round_robin', 'swiss']);
    $table->decimal('buy_in_amount', 10, 2);
    $table->enum('buy_in_currency', ['real_money', 'bonus_chips', 'hard_currency']);
    $table->decimal('prize_pool', 10, 2);
    $table->json('prize_distribution');
    $table->integer('max_participants');
    $table->integer('min_participants');
    $table->enum('status', ['scheduled', 'registration_open', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
    $table->json('phase_rules')->nullable();
    $table->timestamp('starts_at');
    $table->timestamp('ends_at')->nullable();
    $table->timestamps();
    
    $table->index(['status', 'starts_at']);
    $table->index(['title_id', 'status']);
});

Schema::create('tournament_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->integer('seed')->nullable(); // for bracket placement
    $table->integer('rank')->nullable(); // final placement
    $table->decimal('winnings', 10, 2)->default(0.00);
    $table->enum('status', ['registered', 'active', 'eliminated', 'withdrew'])->default('registered');
    $table->timestamp('registered_at');
    
    $table->unique(['tournament_id', 'user_id']);
    $table->index(['tournament_id', 'status']);
});
```

## Schema Changes to Existing Tables

### Add Economy Fields to Users Table

**Migration**:
```php
Schema::table('users', function (Blueprint $table) {
    // Add stripe customer ID for Cashier integration
    $table->string('stripe_customer_id')->nullable()->after('email_verified_at');
    
    // Add daily free strikes tracking
    $table->integer('daily_strikes_remaining')->default(3)->after('level');
    $table->date('strikes_reset_date')->nullable()->after('daily_strikes_remaining');
    
    // Add monthly quota tracking
    $table->integer('monthly_quota_used')->default(0)->after('strikes_reset_date');
    $table->date('quota_reset_date')->nullable()->after('monthly_quota_used');
});
```

### Add Outcome Fields to Games Table

**Migration**:
```php
Schema::table('games', function (Blueprint $table) {
    // Add detailed outcome tracking
    $table->json('final_scores')->nullable()->after('winner_id');
    $table->integer('xp_awarded')->nullable()->after('final_scores');
    $table->json('rewards')->nullable()->after('xp_awarded'); // achievements, items, etc.
    $table->enum('completion_type', ['normal', 'concede', 'abandon', 'timeout'])->nullable()->after('rewards');
});
```

## Entity Relationships Diagram

```
User
├── has many Games (player)
├── has many Alerts
├── has one Balance
├── has many Transactions
├── has many MatchmakingSignals
├── has many Proposals (sent)
├── has many Proposals (received)
└── belongs to many Tournaments

Title
├── has many Games
├── has many Lobbies
├── has many MatchmakingSignals
├── has many Proposals
└── has many Tournaments

Game
├── belongs to Title
├── belongs to many Users (players)
├── has many GameActions
├── has many GameEvents
└── may belong to Tournament

Lobby
├── belongs to User (host)
├── belongs to Title
└── belongs to many Users (players)

MatchmakingSignal
├── belongs to User
└── belongs to Title

Proposal
├── belongs to User (sender)
├── belongs to User (recipient)
├── belongs to Title
└── may belong to Game (previous_game)

Balance
└── belongs to User

Transaction
└── belongs to User

Tournament
├── belongs to Title
├── belongs to many Users (participants)
└── has many Games (matches)

Alert
└── belongs to User

SubscriptionPlan
└── has many Users (subscribers)
```

## Data Transfer Objects (DTOs)

### System Namespace

```php
class HealthStatusData extends Data
{
    public function __construct(
        public bool $healthy,
        public string $status, // 'operational', 'degraded', 'down'
        public array $services, // ['database' => 'ok', 'cache' => 'ok', etc.]
        public string $timestamp,
    ) {}
}

class ConfigData extends Data
{
    public function __construct(
        public string $version,
        public array $supportedGames,
        public array $features, // feature flags
        public ?string $maintenanceWindow = null,
    ) {}
}
```

### Library Namespace

```php
class GameTitleData extends Data
{
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public int $playerCount,
        public PacingType $pacing,
        public ComplexityLevel $complexity,
        public array $categoryTags,
        public ?string $thumbnail = null,
        public ?int $averageSessionMinutes = null,
    ) {}
}

class GameEntityData extends Data
{
    public function __construct(
        public string $type, // 'card', 'unit', 'board', etc.
        public string $id,
        public string $name,
        public array $attributes,
        public ?string $imageUrl = null,
    ) {}
}
```

### Account Namespace

```php
class ProfileData extends Data
{
    public function __construct(
        public string $username,
        public string $email,
        public ?string $avatarUrl,
        public ?string $bio,
        public string $createdAt,
    ) {}
}

class ProgressionData extends Data
{
    public function __construct(
        public int $level,
        public int $currentXp,
        public int $xpToNextLevel,
        public ?BattlePassData $battlePass,
    ) {}
}

class RecordData extends Data
{
    public function __construct(
        public string $titleKey,
        public int $totalGames,
        public int $wins,
        public int $losses,
        public float $winRate,
        public int $eloRating,
    ) {}
}
```

### Floor Namespace

```php
class LobbyData extends Data
{
    public function __construct(
        public string $ulid,
        public string $hostUsername,
        public string $titleKey,
        public string $visibility,
        public int $currentPlayers,
        public int $maxPlayers,
        public ?string $joinCode,
        public string $status,
    ) {}
}

class SignalData extends Data
{
    public function __construct(
        public string $ulid,
        public string $titleKey,
        public int $eloRating,
        public string $connectionQuality,
        public string $expiresAt,
    ) {}
}

class ProposalData extends Data
{
    public function __construct(
        public string $ulid,
        public string $type,
        public string $senderUsername,
        public string $recipientUsername,
        public string $titleKey,
        public array $gameSettings,
        public string $status,
        public string $expiresAt,
    ) {}
}
```

### Economy Namespace

```php
class BalanceData extends Data
{
    public function __construct(
        public int $clientId,
        public string $clientName,
        public float $tokens,
        public float $chips,
        public float $lockedInGames,
    ) {}
}

class TransactionData extends Data
{
    public function __construct(
        public string $ulid,
        public string $type,        // Transaction type enum
        public float $amount,
        public ?string $currency,   // tokens, chips, or usd
        public ?int $clientId,      // Required for balance transactions
        public ?string $clientName, // For display
        public ?int $subscriptionId,        // For subscription-related payments
        public ?string $paymentProvider,    // stripe, google_play, apple_store, telegram
        public ?string $providerTransactionId,
        public ?string $paymentStatus,      // pending, completed, failed, refunded
        public ?string $reference,  // Client reference for balance ops
        public string $createdAt,
    ) {}
}

class PlanData extends Data
{
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public float $price,
        public string $billingCycle,
        public array $features,
    ) {}
}
```

### Competitions Namespace

```php
class CompetitionData extends Data
{
    public function __construct(
        public string $ulid,
        public string $name,
        public string $titleKey,
        public string $format,
        public float $buyInAmount,
        public string $buyInCurrency,
        public float $prizePool,
        public int $currentParticipants,
        public int $maxParticipants,
        public string $status,
        public string $startsAt,
    ) {}
}

class BracketData extends Data
{
    public function __construct(
        public array $rounds, // nested structure of matches
        public array $participants,
        public ?string $currentRound,
    ) {}
}

class StandingData extends Data
{
    public function __construct(
        public int $rank,
        public string $username,
        public int $wins,
        public int $losses,
        public int $points,
        public string $status,
    ) {}
}
```

### Feeds Namespace

```php
class GameFeedEventData extends Data
{
    public function __construct(
        public string $gameUlid,
        public string $eventType, // 'started', 'completed', 'move'
        public string $titleKey,
        public array $players, // array of ['username', 'avatar']
        public ?array $stakes, // ['amount', 'currency']
        public string $timestamp,
    ) {}
}

class WinFeedEventData extends Data
{
    public function __construct(
        public string $gameUlid,
        public string $winnerUsername,
        public string $winnerAvatar,
        public string $titleKey,
        public ?array $stakes, // ['amount', 'currency']
        public string $outcome, // 'checkmate', 'resignation', etc.
        public int $xpEarned,
        public string $timestamp,
    ) {}
}

class LeaderboardFeedEventData extends Data
{
    public function __construct(
        public string $eventType, // 'rank_change', 'new_high_score', 'period_leader'
        public string $username,
        public string $avatar,
        public ?int $oldRank,
        public int $newRank,
        public ?string $titleKey, // null for global leaderboard
        public string $period, // 'daily', 'weekly', 'all_time'
        public int $score,
        public string $timestamp,
    ) {}
}

class TournamentFeedEventData extends Data
{
    public function __construct(
        public string $tournamentUlid,
        public string $tournamentName,
        public string $eventType, // 'round_complete', 'bracket_update', 'elimination', 'winner'
        public ?string $playerUsername,
        public ?int $roundNumber,
        public ?string $matchResult,
        public string $timestamp,
    ) {}
}

class ChallengeFeedEventData extends Data
{
    public function __construct(
        public string $proposalUlid,
        public string $eventType, // 'issued', 'accepted', 'completed'
        public string $challengerUsername,
        public ?string $opponentUsername,
        public string $titleKey,
        public ?array $stakes, // ['amount', 'currency']
        public string $timestamp,
    ) {}
}

class AchievementFeedEventData extends Data
{
    public function __construct(
        public string $achievementKey,
        public string $achievementName,
        public string $description,
        public string $rarity, // 'common', 'rare', 'epic', 'legendary'
        public string $username,
        public string $avatar,
        public ?string $titleKey, // null for platform-wide achievements
        public string $timestamp,
    ) {}
}
```

## State Transitions

### MatchmakingSignal States
```
created → (expires after 5 min or matched) → deleted
```

### Proposal States
```
pending → accepted → creates game
pending → declined → closed
pending → (expires after 5 min) → expired
pending → cancelled → closed
```

### Balance Operations (Entertainment Only)
```
Game Buy-in: tokens/chips -= amount, locked_in_games += amount
Game Cash-out: locked_in_games -= amount, tokens/chips += amount
Cashier Add: tokens/chips += amount (approved clients only)
Cashier Remove: tokens/chips -= amount (approved clients only)
```

Note: All operations use virtual currency for entertainment purposes only. No real money or cryptocurrency transactions occur.

### Tournament States
```
scheduled → registration_open → in_progress → completed
                                            ↘ cancelled
```

## Summary

This data model introduces 4 new entities (MatchmakingSignal, Proposal, Balance, Tournament) and enhances 2 existing entities (User, Game) to support the new API structure. All new models follow Laravel conventions and integrate seamlessly with existing relationships.
