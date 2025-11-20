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
- Has one Balance
- Has many Transactions
- Has many MatchmakingSignals
- Has many Proposals (sent and received)

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
*Already exists* - Financial record

**Fields**:
- `id`: bigint, primary key
- `ulid`: string(26), unique, indexed
- `user_id`: foreign key to users
- `type`: enum(deposit, purchase, buy_in, cash_out, subscription, refund)
- `amount`: decimal(10,2)
- `currency`: string(3), default 'USD'
- `status`: enum(pending, completed, failed, refunded)
- `provider`: enum(stripe, apple, google, telegram), nullable
- `provider_transaction_id`: string, nullable, indexed
- `metadata`: json
- `created_at`: timestamp
- `updated_at`: timestamp

**Relationships**:
- Belongs to User

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
*New* - User's currency holdings

**Fields**:
- `id`: bigint, primary key
- `user_id`: foreign key to users, unique
- `real_money`: decimal(10,2), default 0.00 (withdrawable)
- `bonus_chips`: decimal(10,2), default 0.00 (non-withdrawable)
- `hard_currency`: integer, default 0 (premium currency)
- `locked_in_games`: decimal(10,2), default 0.00 (currently in active games)
- `updated_at`: timestamp

**Indexes**:
- Unique index on user_id (one balance per user)

**Relationships**:
- Belongs to User

**Validation Rules**:
- All balance fields must be >= 0
- Cannot withdraw real_money while locked_in_games > 0
- Bonus chips cannot be withdrawn, only used for game buy-ins

**Migration**:
```php
Schema::create('balances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
    $table->decimal('real_money', 10, 2)->default(0.00);
    $table->decimal('bonus_chips', 10, 2)->default(0.00);
    $table->integer('hard_currency')->default(0);
    $table->decimal('locked_in_games', 10, 2)->default(0.00);
    $table->timestamp('updated_at');
    
    // Ensure balances cannot go negative
    $table->check('real_money >= 0');
    $table->check('bonus_chips >= 0');
    $table->check('hard_currency >= 0');
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
        public float $realMoney,
        public float $bonusChips,
        public int $hardCurrency,
        public float $lockedInGames,
    ) {}
}

class TransactionData extends Data
{
    public function __construct(
        public string $ulid,
        public string $type,
        public float $amount,
        public string $currency,
        public string $status,
        public ?string $provider,
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

### Balance Operations
```
Buy-in: real_money -= amount, locked_in_games += amount
Cash-out: locked_in_games -= amount, real_money += amount
Deposit: real_money += amount
Purchase: real_money -= amount
```

### Tournament States
```
scheduled → registration_open → in_progress → completed
                                            ↘ cancelled
```

## Summary

This data model introduces 4 new entities (MatchmakingSignal, Proposal, Balance, Tournament) and enhances 2 existing entities (User, Game) to support the new API structure. All new models follow Laravel conventions and integrate seamlessly with existing relationships.
