# Implementation Plan: Production-Ready V1 API Structure

**Branch**: `008-api-structure` | **Date**: November 20, 2025 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/008-api-structure/spec.md`

## Summary

Finalize the v1 API structure for production by reorganizing endpoints into a headless infrastructure architecture with 9 logical namespaces: System & Webhooks, Game Library, Authentication, Account Management, Floor Coordination, Active Games, Economy, Data Feeds, and Competitions. This restructure migrates from the legacy scattered endpoint design to a cohesive RESTful API that separates platform services from gameplay, introduces the `/economy` namespace for virtual balance tracking (entertainment only - no real money/crypto transactions), and establishes the `/floor` coordination namespace for unified matchmaking.

**Important Note on Economy**: The `/economy` namespace tracks virtual tokens and chips for entertainment purposes only. This system does not transact real money or cryptocurrency. Approved client applications manage user balances through the cashier service.

## Technical Context

**Language/Version**: PHP 8.3+ (currently using PHP 8.4)  
**Primary Dependencies**: Laravel Framework 12.10, Laravel Sanctum 4.2, Laravel Cashier 16.0, Laravel Reverb 1.6, Spatie Laravel Data 4.5  
**Storage**: MySQL 8.0+ (utilizing JSON columns for game state, efficient indexing for queries)  
**Testing**: Pest 4.1 with Pest Plugin Laravel 4.0 (TDD approach with Feature/Integration/Unit test organization)  
**Target Platform**: Linux server (API-only, headless backend serving multiple frontend clients)  
**Project Type**: Web API (single Laravel application with RESTful endpoints)  
**Performance Goals**: <200ms response time for game state sync, <100ms for health checks, handle 1000+ concurrent users, support 10,000+ SSE connections  
**Constraints**: 99.9% uptime SLA, zero data loss during network interruptions, idempotent action processing, backward compatibility for one major version  
**Scale/Scope**: 1,000+ game titles, 10,000+ concurrent users, 50+ API endpoints across 9 namespaces, support for 4 authentication providers (email, Google, Apple, social)

## Implementation Phases

### Phase 1: Controller Namespace Reorganization

**Objective**: Restructure all controllers into 9 logical namespaces, replacing scattered endpoint design with organized API architecture.

**Strategy**: Direct migration without backward compatibility. Delete old controllers after moving logic to new namespace structure.

**Controller Reorganization Map**:

#### 1. System Namespace (`Api/V1/System/`)
- **Create `HealthController.php`** - Replaces/expands `StatusController`
  - Service health indicators (database, cache, queue, game engine)
  - DELETE: `StatusController.php`
- **Create `TimeController.php`** - New
  - Authoritative server time for client sync
- **Create `ConfigController.php`** - New
  - Global platform configuration (feature flags, supported games, API version)

#### 2. Webhooks Namespace (`Api/V1/Webhooks/`)
- **Create `WebhookController.php`** - Consolidates all webhook handling
  - Stripe, Apple, Google Play, Telegram event processing
  - DELETE: `StripeWebhookController.php`

#### 3. Library Namespace (`Api/V1/Library/`)
- **Create `GameLibraryController.php`** - Rename from `TitleController`
  - Browse games, game metadata, entity definitions
  - DELETE: `TitleController.php`
- **Move `GameRulesController.php`** - No changes, relocate to namespace folder

#### 4. Auth Namespace (`Api/V1/Auth/`)
- **Keep single `AuthController.php`** - Move to namespace folder
  - Authentication flows are cohesive (register, login, social, logout)
  - No split into multiple controllers

#### 5. Account Namespace (`Api/V1/Account/`)
- **Move `ProfileController.php`** - Relocate to namespace folder
- **Create `ProgressionController.php`** - Rename from `UserLevelsController`
  - XP, levels, battle pass progression
  - DELETE: `UserLevelsController.php`
- **Create `RecordsController.php`** - Rename from `UserStatsController`
  - Win/loss statistics, ELO ratings, performance metrics
  - DELETE: `UserStatsController.php`
- **Create `AlertsController.php`** - Rename from `AlertController`
  - Notifications, invites, announcements (plural naming)
  - DELETE: `AlertController.php`

#### 6. Floor Namespace (`Api/V1/Floor/`)
- **Merge into `LobbyController.php`** - Consolidate lobby + lobby player logic
  - Lobby management + player seat management in single controller
  - DELETE: `LobbyPlayerController.php`
- **Create `SignalController.php`** - Rename from `QuickplayController`
  - Matchmaking signals (quickplay/ranked intent)
  - DELETE: `QuickplayController.php`
- **Create `ProposalController.php`** - Rename from `RematchController`
  - Direct challenges + rematch offers (unified proposals)
  - DELETE: `RematchController.php`

#### 7. Games Namespace (`Api/V1/Games/`)
- **Refactor `GameController.php`** - Simplify to listing + state retrieval only
  - Remove forfeit, history methods (extract to dedicated controllers)
- **Keep `GameActionController.php`** - Already focused on action execution
- **Create `GameTurnController.php`** - New
  - Turn timer queries, time remaining
- **Create `GameTimelineController.php`** - Extract from `GameController`
  - Event history, replay data (moved from `history` method)
- **Create `GameConcedeController.php`** - Extract from `GameController`
  - Graceful resignation (moved from `forfeit` method)
- **Create `GameAbandonController.php`** - New
  - Rage quit with penalty
- **Create `GameOutcomeController.php`** - New
  - Final results, winner, scores, XP, rewards

#### 8. Economy Namespace (`Api/V1/Economy/`)
- **Create `BalanceController.php`** - New
  - Virtual token/chip balance queries
- **Create `TransactionController.php`** - New
  - Balance history, transaction listing
- **Create `CashierController.php`** - New
  - Balance adjustments for approved clients
- **Create `PlanController.php`** - Extract from `BillingController`
  - Membership tier listing (moved from `/billing/plans`)
  - DELETE: `BillingController.php`
- **Create `ReceiptController.php`** - Extract from `BillingController`
  - Apple/Google/Telegram IAP verification (moved from billing methods)

#### 9. Feeds Namespace (`Api/V1/Feeds/`)
- **Move `LeaderboardController.php`** - Relocate to namespace folder
  - Real-time leaderboard feeds
- **Create `LiveScoresController.php`** - New
  - SSE stream for live game activity
- **Create `CasinoFloorController.php`** - New
  - SSE stream for floor activity (lobbies, challenges, wins)

#### 10. Competitions Namespace (`Api/V1/Competitions/`)
- **Create `CompetitionController.php`** - New
  - Tournament listing, details
- **Create `EntryController.php`** - New
  - Tournament registration
- **Create `StructureController.php`** - New
  - Phase rules, tournament configuration
- **Create `BracketController.php`** - New
  - Tournament bracket/tree visualization
- **Create `StandingsController.php`** - New
  - Tournament leaderboards, rankings

**Controllers to DELETE** (after migration):
1. ❌ `StatusController.php` → Replaced by System/HealthController
2. ❌ `StripeWebhookController.php` → Replaced by Webhooks/WebhookController
3. ❌ `TitleController.php` → Replaced by Library/GameLibraryController
4. ❌ `UserLevelsController.php` → Replaced by Account/ProgressionController
5. ❌ `UserStatsController.php` → Replaced by Account/RecordsController
6. ❌ `AlertController.php` → Replaced by Account/AlertsController
7. ❌ `LobbyPlayerController.php` → Merged into Floor/LobbyController
8. ❌ `QuickplayController.php` → Replaced by Floor/SignalController
9. ❌ `RematchController.php` → Replaced by Floor/ProposalController
10. ❌ `BillingController.php` → Split into Economy/PlanController + Economy/ReceiptController

**Files Remaining** (relocated to namespaces):
- ✅ `AuthController.php` → Auth/AuthController.php
- ✅ `ProfileController.php` → Account/ProfileController.php
- ✅ `GameController.php` → Games/GameController.php (refactored)
- ✅ `GameActionController.php` → Games/GameActionController.php
- ✅ `GameRulesController.php` → Library/GameRulesController.php
- ✅ `LobbyController.php` → Floor/LobbyController.php (expanded)
- ✅ `LeaderboardController.php` → Feeds/LeaderboardController.php

---

### Phase 2: Route Restructuring

**Objective**: Update all routes in `routes/api.php` to match new namespace organization and RESTful endpoint patterns.

**Strategy**: Complete route rewrite organized by namespace groups. Remove all old route definitions.

**Route Organization**:

```php
// routes/api.php structure

Route::prefix('v1')->group(function () {
    
    // ============================================================
    // SYSTEM NAMESPACE - Public health & config endpoints
    // ============================================================
    Route::prefix('system')->group(function () {
        Route::get('/health', [System\HealthController::class, 'index']);
        Route::get('/time', [System\TimeController::class, 'index']);
        Route::get('/config', [System\ConfigController::class, 'index']);
    });
    
    // ============================================================
    // WEBHOOKS NAMESPACE - External provider callbacks
    // ============================================================
    Route::prefix('webhooks')->controller(Webhooks\WebhookController::class)->group(function () {
        Route::post('/stripe', 'stripe');
        Route::post('/apple', 'apple');
        Route::post('/google', 'google');
        Route::post('/telegram', 'telegram');
    });
    
    // ============================================================
    // LIBRARY NAMESPACE - Public game discovery
    // ============================================================
    Route::prefix('library')->group(function () {
        Route::get('/', [Library\GameLibraryController::class, 'index']);
        Route::get('/{key}', [Library\GameLibraryController::class, 'show']);
        Route::get('/{key}/rules', [Library\GameRulesController::class, 'show']);
        Route::get('/{key}/entities', [Library\GameLibraryController::class, 'entities']);
    });
    
    // ============================================================
    // AUTH NAMESPACE - Authentication flows
    // ============================================================
    Route::prefix('auth')->controller(Auth\AuthController::class)->group(function () {
        // Public routes
        Route::post('/register', 'register');
        Route::post('/verify', 'verify');
        Route::post('/login', 'login');
        Route::post('/social', 'socialLogin');
        
        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', 'logout');
            Route::get('/user', 'getUser');
            Route::patch('/user', 'updateUser');
        });
    });
    
    // ============================================================
    // AUTHENTICATED ROUTES - Require Sanctum token
    // ============================================================
    Route::middleware('auth:sanctum')->group(function () {
        
        // ACCOUNT NAMESPACE - User profile & progression
        Route::prefix('account')->group(function () {
            Route::get('/profile', [Account\ProfileController::class, 'show']);
            Route::patch('/profile', [Account\ProfileController::class, 'update']);
            Route::get('/progression', [Account\ProgressionController::class, 'show']);
            Route::get('/records', [Account\RecordsController::class, 'show']);
            Route::get('/alerts', [Account\AlertsController::class, 'index']);
            Route::post('/alerts/read', [Account\AlertsController::class, 'markAsRead']);
        });
        
        // FLOOR NAMESPACE - Matchmaking coordination
        Route::prefix('floor')->group(function () {
            // Lobbies
            Route::prefix('lobbies')->controller(Floor\LobbyController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('/{ulid}', 'show');
                Route::delete('/{ulid}', 'destroy');
                Route::post('/{ulid}/ready-check', 'readyCheck');
                Route::post('/{ulid}/seat', 'joinSeat');         // Merged from LobbyPlayerController
                Route::put('/{ulid}/seat/{position}', 'updateSeat');  // Merged
                Route::delete('/{ulid}/seat', 'leaveSeat');      // Merged
            });
            
            // Matchmaking Signals
            Route::prefix('signals')->controller(Floor\SignalController::class)->group(function () {
                Route::post('/', 'store');
                Route::delete('/{ulid}', 'destroy');
            });
            
            // Proposals (Challenges + Rematches)
            Route::prefix('proposals')->controller(Floor\ProposalController::class)->group(function () {
                Route::post('/', 'store');
                Route::post('/{ulid}/accept', 'accept');
                Route::post('/{ulid}/decline', 'decline');
            });
        });
        
        // GAMES NAMESPACE - Active gameplay
        Route::prefix('games')->group(function () {
            Route::get('/', [Games\GameController::class, 'index']);
            Route::get('/{ulid}', [Games\GameController::class, 'show']);
            
            // Game-specific actions
            Route::post('/{ulid}/actions', [Games\GameActionController::class, 'store']);
            Route::get('/{ulid}/actions/options', [Games\GameActionController::class, 'options']);
            Route::get('/{ulid}/turn', [Games\GameTurnController::class, 'show']);
            Route::get('/{ulid}/timeline', [Games\GameTimelineController::class, 'index']);
            Route::post('/{ulid}/concede', [Games\GameConcedeController::class, 'store']);
            Route::post('/{ulid}/abandon', [Games\GameAbandonController::class, 'store']);
            Route::get('/{ulid}/outcome', [Games\GameOutcomeController::class, 'show']);
        });
        
        // ECONOMY NAMESPACE - Virtual balance & subscriptions
        Route::prefix('economy')->group(function () {
            Route::get('/balance', [Economy\BalanceController::class, 'show']);
            Route::get('/transactions', [Economy\TransactionController::class, 'index']);
            Route::post('/cashier', [Economy\CashierController::class, 'store']);  // Approved clients only
            Route::get('/plans', [Economy\PlanController::class, 'index']);
            Route::post('/receipts/{provider}', [Economy\ReceiptController::class, 'verify']);
        });
        
        // FEEDS NAMESPACE - Real-time SSE streams
        Route::prefix('feeds')->group(function () {
            Route::get('/games', [Feeds\LiveScoresController::class, 'games']);
            Route::get('/wins', [Feeds\LiveScoresController::class, 'wins']);
            Route::get('/leaderboards', [Feeds\LeaderboardController::class, 'stream']);
            Route::get('/tournaments', [Feeds\LiveScoresController::class, 'tournaments']);
            Route::get('/challenges', [Feeds\CasinoFloorController::class, 'challenges']);
            Route::get('/achievements', [Feeds\CasinoFloorController::class, 'achievements']);
        });
        
        // COMPETITIONS NAMESPACE - Tournament management
        Route::prefix('competitions')->group(function () {
            Route::get('/', [Competitions\CompetitionController::class, 'index']);
            Route::get('/{ulid}', [Competitions\CompetitionController::class, 'show']);
            Route::post('/{ulid}/enter', [Competitions\EntryController::class, 'store']);
            Route::get('/{ulid}/structure', [Competitions\StructureController::class, 'show']);
            Route::get('/{ulid}/bracket', [Competitions\BracketController::class, 'show']);
            Route::get('/{ulid}/standings', [Competitions\StandingsController::class, 'index']);
        });
    });
});
```

**Old Routes to DELETE**:
- ❌ `GET /v1/status` → `GET /v1/system/health`
- ❌ `POST /v1/stripe/webhook` → `POST /v1/webhooks/stripe`
- ❌ `GET /v1/titles` → `GET /v1/library`
- ❌ `GET /v1/titles/{gameTitle}/rules` → `GET /v1/library/{key}/rules`
- ❌ `GET /v1/leaderboard/{gameTitle}` → `GET /v1/feeds/leaderboards` (SSE)
- ❌ All `/v1/billing/*` routes → Replaced by `/v1/economy/*`
- ❌ All `/v1/me/*` routes → Replaced by `/v1/account/*`
- ❌ All `/v1/games/quickplay/*` → Replaced by `/v1/floor/signals/*`
- ❌ All `/v1/games/lobbies/*` → Replaced by `/v1/floor/lobbies/*`
- ❌ `/v1/games/rematch/*` → Replaced by `/v1/floor/proposals/*`

**Endpoint Count**: 50+ endpoints organized across 9 namespaces

---

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Status**: ✅ PASSED (Constitution file is template-only, no specific gates defined for this project)

Since the constitution file contains only template placeholders and no concrete project-specific rules, there are no gates to verify. The implementation will follow Laravel best practices and the existing project patterns established in prior features.

## Project Structure

### Documentation (this feature)

```text
specs/008-api-structure/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
│   ├── system.openapi.yaml
│   ├── library.openapi.yaml
│   ├── auth.openapi.yaml
│   ├── account.openapi.yaml
│   ├── floor.openapi.yaml
│   ├── games.openapi.yaml
│   ├── economy.openapi.yaml
│   ├── feeds.openapi.yaml
│   └── competitions.openapi.yaml
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           └── V1/
│               ├── System/
│               │   ├── HealthController.php
│               │   ├── TimeController.php
│               │   └── ConfigController.php
│               ├── Webhooks/
│               │   └── WebhookController.php
│               ├── Library/
│               │   ├── GameLibraryController.php
│               │   └── GameRulesController.php
│               ├── Auth/
│               │   ├── RegisterController.php
│               │   ├── LoginController.php
│               │   ├── SocialAuthController.php
│               │   └── LogoutController.php
│               ├── Account/
│               │   ├── ProfileController.php
│               │   ├── ProgressionController.php
│               │   ├── RecordsController.php
│               │   └── AlertsController.php
│               ├── Floor/
│               │   ├── LobbyController.php
│               │   ├── SignalController.php
│               │   └── ProposalController.php
│               ├── Games/
│               │   ├── GameController.php
│               │   ├── GameActionController.php
│               │   ├── GameTurnController.php
│               │   ├── GameTimelineController.php
│               │   ├── GameConcedeController.php
│               │   ├── GameAbandonController.php
│               │   └── GameOutcomeController.php
│               ├── Economy/
│               │   ├── BalanceController.php
│               │   ├── TransactionController.php
│               │   ├── CashierController.php
│               │   ├── PlanController.php
│               │   └── ReceiptController.php
│               ├── Feeds/
│               │   ├── LiveScoresController.php
│               │   └── CasinoFloorController.php
│               └── Competitions/
│                   ├── CompetitionController.php
│                   ├── EntryController.php
│                   ├── StructureController.php
│                   ├── BracketController.php
│                   └── StandingsController.php
├── Models/
│   ├── User.php (existing)
│   ├── Game.php (existing)
│   ├── Lobby.php (existing)
│   ├── Mode.php (existing)
│   ├── MatchmakingSignal.php (new)
│   ├── Proposal.php (new - rename from RematchRequest)
│   ├── Transaction.php (existing)
│   ├── Balance.php (existing)
│   ├── Tournament.php (new)
│   ├── PlanAudit.php (new)
│   └── Alert.php (existing)
├── Enums/
│   ├── GameTitle.php (existing - version controlled)
│   └── MembershipPlan.php (new - version controlled)
├── Services/
│   ├── SystemHealthService.php (new)
│   ├── GameLibraryService.php (new)
│   ├── FloorCoordinationService.php (new)
│   ├── EconomyService.php (new)
│   └── CompetitionService.php (new)
│   ├── SystemHealthService.php (new)
│   ├── GameLibraryService.php (new)
│   ├── FloorCoordinationService.php (new)
│   ├── EconomyService.php (new)
│   └── CompetitionService.php (new)
└── DataTransferObjects/
    ├── System/
    │   ├── HealthStatusData.php
    │   └── ConfigData.php
    ├── Library/
    │   ├── GameTitleData.php
    │   └── GameEntityData.php
    ├── Account/
    │   ├── ProfileData.php
    │   ├── ProgressionData.php
    │   └── RecordData.php
    ├── Floor/
    │   ├── LobbyData.php
    │   ├── SignalData.php
    │   └── ProposalData.php
    ├── Economy/
    │   ├── BalanceData.php
    │   ├── TransactionData.php
    │   └── PlanData.php
    └── Competitions/
        ├── CompetitionData.php
        ├── BracketData.php
        └── StandingData.php

routes/
├── api.php (restructure with namespace grouping)
└── web.php (unchanged)

tests/
├── Feature/
│   ├── Api/
│   │   └── V1/
│   │       ├── SystemTest.php (new)
│   │       ├── LibraryTest.php (new)
│   │       ├── AuthTest.php (existing)
│   │       ├── AccountTest.php (new)
│   │       ├── FloorTest.php (new)
│   │       ├── GamesTest.php (existing)
│   │       ├── EconomyTest.php (new)
│   │       ├── FeedsTest.php (new)
│   │       └── CompetitionsTest.php (new)
├── Integration/
│   └── ApiContractTest.php (new - validates OpenAPI compliance)
└── Unit/
    ├── Services/
    │   ├── SystemHealthServiceTest.php (new)
    │   ├── GameLibraryServiceTest.php (new)
    │   ├── FloorCoordinationServiceTest.php (new)
    │   ├── EconomyServiceTest.php (new)
    │   └── CompetitionServiceTest.php (new)
    └── DataTransferObjects/
        └── [corresponding DTO tests]

database/
└── migrations/
    ├── create_matchmaking_signals_table.php (new - rename/modify existing quickplay migration)
    ├── create_proposals_table.php (new - rename/modify existing rematch_requests migration)
    ├── create_balances_table.php (existing - 2025_11_20_000001)
    ├── create_transactions_table.php (existing - 2025_11_20_000002)
    ├── create_tournaments_table.php (new)
    ├── create_tournament_user_table.php (new - pivot table)
    ├── add_stripe_customer_to_users_table.php (new)
    ├── add_outcome_fields_to_games_table.php (new)
    └── add_membership_plan_to_subscription_items_table.php (new - after MembershipPlan enum created)
```

**Migration Strategy Notes**:
- **MatchmakingSignals**: Rename/modify existing quickplay migration (if it exists) to match Floor namespace spec
- **Proposals**: Rename/modify existing `rematch_requests` table (2025_11_17_035314) to support both challenges and rematches
- **Balances/Transactions**: Already implemented with multi-client architecture
- **Tournaments**: New tables for competitive play with tokens/chips buy-ins
- **Users**: Add `stripe_customer_id` for Cashier integration (strikes/quotas tracked in separate tables)
- **Games**: Add `final_scores`, `xp_awarded`, `rewards` fields (outcome_type already exists)
- **SubscriptionItems**: Add `membership_plan` enum field after creating MembershipPlan enum

**Architecture Notes**:
- **No Titles Table**: Using version-controlled `GameTitle` enum instead (connect-four, checkers, hearts, spades)
- **No Subscription Plans Table**: Using version-controlled `MembershipPlan` enum instead (Free, Pro, Elite, etc.)
- **Database References**: Combine `title_slug` (string) + `mode_id` (foreign key) to reference games
- **Existing Tables**: `modes` table stores game modes with composite key (`title_slug`, `slug`)
```

**Structure Decision**: Web API (Option 2 variant - single Laravel backend)

This is a Laravel API-only application serving multiple frontend clients. The structure follows Laravel conventions with controllers organized by API namespace (System, Library, Auth, Account, Floor, Games, Economy, Feeds, Competitions). Services handle business logic, DTOs provide type-safe data transfer, and tests mirror the controller structure. No frontend code exists in this repository - it's a headless backend.

## Complexity Tracking

**Status**: No Constitution violations to justify (constitution is template-only)

This feature involves refactoring existing endpoints into a more organized structure rather than adding new complexity. The reorganization actually reduces complexity by:
- Grouping related endpoints into logical namespaces
- Separating platform services (System, Library, Auth, Account) from gameplay (Floor, Games)
- Isolating financial operations into dedicated Economy namespace
- Making API structure more discoverable and maintainable

## Implementation Clarifications (Updated November 20, 2025)

### Database Architecture Decisions

**Version-Controlled Enums (No Database Tables)**:
- **GameTitle Enum**: Replaces titles table, values stored as `title_slug` string in migrations
  - Current values: `connect-four`, `checkers`, `hearts`, `spades`
  - Located: `app/Enums/GameTitle.php`
  - Usage: Games, Lobbies, MatchmakingSignals, Proposals, Tournaments reference via `title_slug` field

- **MembershipPlan Enum**: Replaces subscription_plans table (to be created)
  - Proposed values: `Free`, `Pro`, `Elite` (exact values TBD)
  - Usage: SubscriptionItems table will have `membership_plan` field after enum creation

**Game References Pattern**:
All game-related tables use composite reference pattern:
- `title_slug` (string) - References GameTitle enum case value
- `mode_id` (foreign key) - References modes table (which also stores title_slug)
- Example: Games table has both `title_slug` and `mode_id`

**Existing Migration Renames/Modifications**:
1. **MatchmakingSignals**: Currently planned as "quickplay" migration (not yet created)
   - Will be named `create_matchmaking_signals_table.php`
   - Supports quickplay and ranked matchmaking with ELO

2. **Proposals**: Existing `rematch_requests` table needs expansion
   - Current: `2025_11_17_035314_create_rematch_requests_table.php`
   - Rename to: `create_proposals_table.php`
   - Add: `type` enum(challenge, rematch), `title_slug`, `mode_id`, `game_settings`, `responded_at`
   - Rename fields: `requesting_user_id` → `sender_id`, `opponent_user_id` → `recipient_id`, `original_game_id` → `previous_game_id`

### Economy Namespace Changes

**Tournament Currency Simplification**:
- **Old spec**: `buy_in_currency enum(real_money, bonus_chips, hard_currency)`
- **New spec**: `buy_in_currency enum(tokens, chips)` with default 'chips'
- **Rationale**: Align with existing Balance table (tokens/chips only), entertainment-only economy principle
- **Typical usage**: Tournaments will primarily use chips

### User Table Changes

**Simplified from original spec**:
- ✅ **Keep**: `stripe_customer_id` (nullable) - For Cashier subscription integration
- ❌ **Remove**: `daily_strikes_remaining`, `strikes_reset_date` - Already tracked in strikes table
- ❌ **Remove**: `monthly_quota_used`, `quota_reset_date` - Already tracked in quotas table

**Separate tables already exist**:
- `strikes` table (2025_11_13_000010) - Tracks daily free games
- `quotas` table (2025_11_13_000011) - Tracks monthly limits

### Games Table Changes

**Partially implemented**:
- ✅ **Existing**: `winner_id`, `winner_position`, `outcome_type` (string), `outcome_details` (json)
- ➕ **Add**: `final_scores` (json), `xp_awarded` (integer), `rewards` (json)
- 🔄 **Consider**: Converting `outcome_type` from string to enum(win, draw, forfeit, timeout)

### Floor Namespace

**New coordination layer for matchmaking**:
- **MatchmakingSignals**: Quickplay/ranked intent with ELO-based matching
- **Proposals**: Direct challenges + rematch requests (unified table)
- **Lobbies**: Private room coordination (already exists)

### Competitions Namespace

**Tournament Structure**:
- **Primary table**: `tournaments` with format enum(single_elimination, double_elimination, round_robin, swiss)
- **Pivot table**: `tournament_user` for participants with seed, rank, winnings, status
- **Integration**: Tournament matches create Game records with tournament_id reference
- **Virtual economy**: Buy-ins and prizes use tokens/chips (entertainment only)

### Outstanding Questions

**To Be Determined**:
1. **User quota fields**: Should `daily_strikes_remaining`/`monthly_quota_used` stay in separate strikes/quotas tables or consolidate into users table?
   - Current decision: Keep in separate tables (strikes/quotas already exist)

2. **MembershipPlan enum values**: Need to define Free/Pro/Elite tier details
   - Action: Create enum before adding field to subscription_items migration

3. **Games table outcome_type**: Keep as string or convert to enum?
   - Current: String with values like 'win', 'draw', 'forfeit', 'timeout'
   - Consideration: Enum conversion for type safety (optional migration)

## Phase 3: Idempotency Implementation

### Redis Configuration

Update `config/database.php` to include dedicated idempotency store:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
    
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => '1',
    ],
    
    'idempotency' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => '2',
        'options' => [
            'prefix' => 'idempotency:',
        ],
    ],
],
```

### Middleware Implementation

Create `app/Http/Middleware/EnsureIdempotency.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Ramsey\Uuid\Uuid;

class EnsureIdempotency
{
    public function handle(Request $request, Closure $next)
    {
        // Only apply to POST/PUT/DELETE
        if (!in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
            return $next($request);
        }

        $idempotencyKey = $request->header('X-Idempotency-Key');

        if (!$idempotencyKey) {
            return response()->json([
                'error' => 'MISSING_IDEMPOTENCY_KEY',
                'message' => 'X-Idempotency-Key header is required for this operation',
            ], 400);
        }

        // Validate key format (UUID v4 or ULID)
        if (!Uuid::isValid($idempotencyKey) && !$this->isValidUlid($idempotencyKey)) {
            return response()->json([
                'error' => 'INVALID_IDEMPOTENCY_KEY',
                'message' => 'X-Idempotency-Key must be a valid UUID v4 or ULID',
            ], 400);
        }

        $redis = Redis::connection('idempotency');
        $cacheKey = $idempotencyKey;

        // Check for existing cached response
        $cachedResponse = $redis->get($cacheKey);
        if ($cachedResponse) {
            $data = json_decode($cachedResponse, true);
            return response()->json($data['body'], $data['status'])
                ->withHeaders($data['headers'] ?? []);
        }

        // Use distributed lock to prevent concurrent duplicate requests
        $lock = $redis->lock("lock:{$cacheKey}", 10);

        try {
            if (!$lock->get()) {
                // Another request with same key is processing
                return response()->json([
                    'error' => 'REQUEST_IN_PROGRESS',
                    'message' => 'A request with this idempotency key is currently being processed',
                ], 409);
            }

            // Process request
            $response = $next($request);

            // Cache successful response (2xx) for 24 hours
            if ($response->status() >= 200 && $response->status() < 300) {
                $redis->setex(
                    $cacheKey,
                    86400, // 24 hours
                    json_encode([
                        'status' => $response->status(),
                        'headers' => $response->headers->all(),
                        'body' => json_decode($response->getContent(), true),
                    ])
                );
            }

            return $response;

        } finally {
            $lock->release();
        }
    }

    private function isValidUlid(string $value): bool
    {
        return preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value) === 1;
    }
}
```

### Apply to Protected Endpoints

Update `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ... existing middleware
    'idempotency' => \App\Http\Middleware\EnsureIdempotency::class,
];
```

Apply to routes in `routes/api.php`:

```php
// Game actions - prevent duplicate moves
Route::post('games/{ulid}/actions', [Games\GameActionController::class, 'store'])
    ->middleware('idempotency');

// Economy operations - prevent duplicate transactions
Route::post('economy/cashier', [Economy\CashierController::class, 'store'])
    ->middleware('idempotency');

// Tournament entry - prevent duplicate enrollments
Route::post('competitions/{ulid}/enter', [Competitions\EntryController::class, 'store'])
    ->middleware('idempotency');

// Proposal acceptance - prevent duplicate accepts
Route::post('floor/proposals/{ulid}/accept', [Floor\ProposalController::class, 'accept'])
    ->middleware('idempotency');
```

### Client Implementation Example

```typescript
// @gamerprotocol/ui package
import { v4 as uuidv4 } from 'uuid';

async function makeIdempotentRequest(url: string, data: any, idempotencyKey?: string) {
  const key = idempotencyKey || uuidv4();
  
  return fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
      'X-Idempotency-Key': key,
    },
    body: JSON.stringify(data),
  });
}
```

## Phase 4: Error Response Standardization

### Standard Error Schema

All error responses follow this structure:

```json
{
  "error": "MACHINE_READABLE_CODE",
  "message": "Human-readable description",
  "correlation_id": "uuid-for-support-tracing",
  "errors": [
    {
      "field": "email",
      "code": "INVALID_FORMAT",
      "message": "Must be a valid email address"
    }
  ]
}
```

### HTTP Status Code Usage

| Status | Usage | Example Error Codes |
|--------|-------|---------------------|
| 400 | Malformed request syntax | `INVALID_JSON`, `MISSING_REQUIRED_FIELD` |
| 401 | Missing/invalid authentication | `INVALID_TOKEN`, `TOKEN_EXPIRED` |
| 403 | Valid auth but insufficient permissions | `INSUFFICIENT_PLAN`, `CLIENT_NOT_APPROVED` |
| 404 | Resource not found | `GAME_NOT_FOUND`, `USER_NOT_FOUND` |
| 409 | Business logic conflict | `TURN_NOT_YOURS`, `LOBBY_FULL`, `ALREADY_ENROLLED` |
| 422 | Validation failed | `VALIDATION_FAILED` (with errors array) |
| 429 | Rate limit exceeded | `RATE_LIMIT_EXCEEDED`, `DAILY_QUOTA_EXCEEDED` |
| 500 | Unexpected server error | `INTERNAL_ERROR` |
| 503 | Service temporarily unavailable | `SERVICE_UNAVAILABLE`, `MAINTENANCE_MODE` |

### Exception Handler

Update `app/Exceptions/Handler.php`:

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Str;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        // Only format JSON for API requests
        if (!$request->is('api/*')) {
            return parent::render($request, $e);
        }

        $correlationId = (string) Str::uuid();
        
        // Log with correlation ID for support tracing
        \Log::error('API Error', [
            'correlation_id' => $correlationId,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Validation errors
        if ($e instanceof ValidationException) {
            return response()->json([
                'error' => 'VALIDATION_FAILED',
                'message' => 'The request contains invalid data',
                'correlation_id' => $correlationId,
                'errors' => collect($e->errors())->map(fn($messages, $field) => [
                    'field' => $field,
                    'code' => 'INVALID_VALUE',
                    'message' => $messages[0],
                ])->values()->all(),
            ], 422);
        }

        // HTTP exceptions
        if ($e instanceof HttpException) {
            return response()->json([
                'error' => $this->getErrorCode($e->getStatusCode()),
                'message' => $e->getMessage() ?: 'An error occurred',
                'correlation_id' => $correlationId,
            ], $e->getStatusCode());
        }

        // Custom business rule exceptions
        if ($e instanceof BusinessRuleException) {
            return response()->json([
                'error' => $e->getErrorCode(),
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], $e->getStatusCode());
        }

        // Generic server errors
        return response()->json([
            'error' => 'INTERNAL_ERROR',
            'message' => app()->environment('production') 
                ? 'An unexpected error occurred' 
                : $e->getMessage(),
            'correlation_id' => $correlationId,
        ], 500);
    }

    private function getErrorCode(int $status): string
    {
        return match($status) {
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            429 => 'RATE_LIMIT_EXCEEDED',
            503 => 'SERVICE_UNAVAILABLE',
            default => 'HTTP_ERROR',
        };
    }
}
```

### Custom Business Rule Exception

Create `app/Exceptions/BusinessRuleException.php`:

```php
<?php

namespace App\Exceptions;

use Exception;

class BusinessRuleException extends Exception
{
    public function __construct(
        private string $errorCode,
        string $message,
        private int $statusCode = 409
    ) {
        parent::__construct($message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    // Factory methods for common errors
    public static function insufficientBalance(string $currency): self
    {
        return new self(
            'INSUFFICIENT_BALANCE',
            "Not enough {$currency} to complete this operation"
        );
    }

    public static function notYourTurn(): self
    {
        return new self(
            'TURN_NOT_YOURS',
            'It is not your turn to play'
        );
    }

    public static function lobbyFull(): self
    {
        return new self(
            'LOBBY_FULL',
            'This lobby has reached maximum capacity'
        );
    }

    public static function maxProposalsExceeded(): self
    {
        return new self(
            'MAX_PROPOSALS_EXCEEDED',
            'You have reached the maximum number of active proposals',
            429
        );
    }
}
```

### Usage in Controllers

```php
use App\Exceptions\BusinessRuleException;

public function store(Request $request)
{
    if ($game->current_player_id !== $request->user()->id) {
        throw BusinessRuleException::notYourTurn();
    }

    if ($user->balance->chips < $tournament->buy_in_amount) {
        throw BusinessRuleException::insufficientBalance('chips');
    }

    // ... continue with business logic
}
```

## Phase 5: Real-Time Sync with Laravel Reverb

### Broadcasting Configuration

Update `config/broadcasting.php`:

```php
'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'app_id' => env('REVERB_APP_ID'),
        'options' => [
            'host' => env('REVERB_HOST'),
            'port' => env('REVERB_PORT', 443),
            'scheme' => env('REVERB_SCHEME', 'https'),
            'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
        ],
    ],
],
```

### Environment Variables

```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=reverb.gamerprotocol.com
REVERB_PORT=443
REVERB_SCHEME=https

# Redis for pub/sub (horizontal scaling)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Channel Authorization

Update `routes/channels.php`:

```php
use Illuminate\Support\Facades\Broadcast;

// Private channel - Game updates (only participants)
Broadcast::channel('games.{gameId}', function ($user, $gameId) {
    $game = \App\Models\Game::where('ulid', $gameId)->firstOrFail();
    
    return $game->players()->where('user_id', $user->id)->exists();
});

// Presence channel - Lobby participants
Broadcast::channel('lobbies.{lobbyId}', function ($user, $lobbyId) {
    $lobby = \App\Models\Lobby::where('ulid', $lobbyId)->firstOrFail();
    
    if ($lobby->players()->where('user_id', $user->id)->exists()) {
        return [
            'id' => $user->ulid,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url,
            'elo' => $user->elo,
        ];
    }
});

// Public channel - Tournament leaderboards (anyone can subscribe)
Broadcast::channel('tournaments.{tournamentId}', function () {
    return true;
});

// Public channel - Global leaderboards
Broadcast::channel('leaderboards.{gameTitle}', function () {
    return true;
});
```

### Event Broadcasting

Create `app/Events/GameActionExecuted.php`:

```php
<?php

namespace App\Events;

use App\Models\Game;
use App\Models\GameAction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameActionExecuted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Game $game,
        public GameAction $action
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("games.{$this->game->ulid}");
    }

    public function broadcastWith(): array
    {
        return [
            'action' => [
                'id' => $this->action->ulid,
                'type' => $this->action->type,
                'data' => $this->action->data,
                'sequence' => $this->action->sequence,
                'executed_at' => $this->action->created_at->toISOString(),
            ],
            'game_state' => [
                'current_player_id' => $this->game->current_player_id,
                'status' => $this->game->status,
                'board' => $this->game->board_state,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.action.executed';
    }
}
```

Create `app/Events/LobbyPlayerJoined.php`:

```php
<?php

namespace App\Events;

use App\Models\Lobby;
use App\Models\User;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LobbyPlayerJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Lobby $lobby,
        public User $user
    ) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel("lobbies.{$this->lobby->ulid}");
    }

    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->user->ulid,
                'username' => $this->user->username,
                'avatar_url' => $this->user->avatar_url,
                'elo' => $this->user->elo,
            ],
            'lobby' => [
                'player_count' => $this->lobby->players()->count(),
                'max_players' => $this->lobby->max_players,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'lobby.player.joined';
    }
}
```

### Dispatch Events in Controllers

```php
use App\Events\GameActionExecuted;

public function store(Request $request, string $ulid)
{
    $game = Game::where('ulid', $ulid)->firstOrFail();
    
    // Execute action logic...
    $action = $this->gameEngine->executeAction($game, $request->validated());
    
    // Broadcast to all game participants
    broadcast(new GameActionExecuted($game, $action))->toOthers();
    
    return response()->json(['action' => $action], 201);
}
```

### Laravel Echo Client Setup

In `@gamerprotocol/ui` npm package, configure Echo:

```typescript
// echo.ts
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/api/v1/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${getAuthToken()}`,
        },
    },
});

// Auto-reconnect with exponential backoff
echo.connector.pusher.connection.bind('disconnected', () => {
    console.warn('WebSocket disconnected, attempting reconnect...');
});

echo.connector.pusher.connection.bind('connected', () => {
    console.log('WebSocket connected');
});
```

### Subscribe to Channels

```typescript
// Subscribe to game updates
export function subscribeToGame(gameId: string, callbacks: {
    onAction: (data: any) => void;
    onStatusChange: (data: any) => void;
}) {
    const channel = echo.private(`games.${gameId}`);
    
    channel.listen('.game.action.executed', callbacks.onAction);
    channel.listen('.game.status.changed', callbacks.onStatusChange);
    
    return () => channel.stopListening();
}

// Subscribe to lobby (presence channel)
export function subscribeToLobby(lobbyId: string, callbacks: {
    onJoin: (user: any) => void;
    onLeave: (user: any) => void;
    onReady: (users: any[]) => void;
}) {
    const channel = echo.join(`lobbies.${lobbyId}`);
    
    channel
        .here(callbacks.onReady)
        .joining(callbacks.onJoin)
        .leaving(callbacks.onLeave)
        .listen('.lobby.player.joined', callbacks.onJoin);
    
    return () => channel.leave();
}

// Subscribe to public tournament updates
export function subscribeToTournament(tournamentId: string, onUpdate: (data: any) => void) {
    const channel = echo.channel(`tournaments.${tournamentId}`);
    
    channel.listen('.tournament.bracket.updated', onUpdate);
    
    return () => channel.stopListening();
}
```

### Catch-Up Sync Endpoint

For clients that disconnect and reconnect, provide catch-up endpoint:

```php
// app/Http/Controllers/Api/V1/Games/GameSyncController.php
public function show(Request $request, string $ulid)
{
    $game = Game::where('ulid', $ulid)->firstOrFail();
    
    // Get actions after specific sequence (for reconnection)
    $afterSequence = $request->query('after_sequence', 0);
    
    $missedActions = $game->actions()
        ->where('sequence', '>', $afterSequence)
        ->orderBy('sequence')
        ->get();
    
    return response()->json([
        'game' => $game,
        'missed_actions' => $missedActions,
        'current_sequence' => $game->actions()->max('sequence') ?? 0,
    ]);
}
```

Route:

```php
Route::get('games/{ulid}/sync', [Games\GameSyncController::class, 'show']);
```

Client usage:

```typescript
// On reconnect, check for missed events
const lastSequence = localStorage.getItem(`game_${gameId}_sequence`) ?? 0;
const sync = await fetch(`/api/v1/games/${gameId}/sync?after_sequence=${lastSequence}`);
const { missed_actions, current_sequence } = await sync.json();

// Apply missed actions
missed_actions.forEach(action => applyGameAction(action));
localStorage.setItem(`game_${gameId}_sequence`, current_sequence);
```

### Redis Pub/Sub for Horizontal Scaling

Reverb automatically uses Redis for pub/sub when configured. Ensure `REDIS_HOST` is set in `.env`.

**Redis Database Separation**:
- Database 0: Default (sessions, general cache)
- Database 1: Laravel cache
- Database 2: Idempotency keys
- Reverb will use Database 0 for pub/sub by default
