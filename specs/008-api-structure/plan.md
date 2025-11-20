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
│   ├── MatchmakingSignal.php (new)
│   ├── Proposal.php (new)
│   ├── Transaction.php (existing)
│   ├── Balance.php (new)
│   ├── SubscriptionPlan.php (existing)
│   ├── Tournament.php (new)
│   └── Alert.php (existing)
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
    ├── create_matchmaking_signals_table.php (new)
    ├── create_proposals_table.php (new)
    ├── create_balances_table.php (new)
    ├── create_tournaments_table.php (new)
    └── add_economy_fields_to_users_table.php (new)
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
