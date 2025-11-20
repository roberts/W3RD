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
