# Implementation Plan: API Test Suite

**Branch**: `005-api-test-suite` | **Date**: 2025-01-16 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/005-api-test-suite/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Create comprehensive API test suite for all client-facing endpoints using Pest v4. Tests will be organized into logical groups using describe() blocks, implement DRY principles through reusable helpers and traits, and cover all authentication, game lifecycle, billing, matchmaking, profile, and alert endpoints. Primary goal is enabling client developers to confidently integrate with the API by providing fast (<30s), reliable (0% flakiness), and maintainable test coverage for all 40+ endpoints.

## Technical Context

**Language/Version**: PHP 8.3  
**Primary Dependencies**: Pest v4.1, Pest Plugin Laravel v4.0, Laravel Framework v12.10, Laravel Sanctum v4.2  
**Storage**: PostgreSQL (via Laravel Eloquent ORM), database factories for test data generation  
**Testing**: Pest v4 with RefreshDatabase trait, Orchestra Testbench v10.6, Mockery v1.6 for mocking  
**Target Platform**: Linux server (CI/CD + local development environments)  
**Project Type**: Web application (Laravel backend API)  
**Performance Goals**: Test suite completes in <30 seconds, individual test groups run in <5 seconds  
**Constraints**: 0% flakiness rate, tests must be idempotent and parallelizable, no external service dependencies in tests  
**Scale/Scope**: 40+ API endpoints across 8 feature domains (Auth, Games, Billing, Quickplay, Lobbies, Profile, Alerts, Public)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

**Status**: ✅ PASS - Constitution file is a template, no project-specific principles defined yet.

**Note**: The repository's `.specify/memory/constitution.md` is currently a template without project-specific principles. This feature adds test coverage for existing API functionality and does not introduce new architectural patterns requiring constitutional review. Standard Laravel/Pest testing practices apply.

## Project Structure

### Documentation (this feature)

```text
specs/005-api-test-suite/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
│   └── test-organization.md  # Test suite structure and grouping
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
tests/
├── Pest.php             # Global test configuration (updated with helpers/expectations)
├── TestCase.php         # Base test case (existing)
├── Feature/
│   ├── Api/
│   │   ├── V1/
│   │   │   ├── AuthenticationTest.php      # Auth endpoints (register, login, verify, social, refresh, logout)
│   │   │   ├── GameLifecycleTest.php       # Game CRUD, actions, options, completion
│   │   │   ├── QuickplayTest.php           # Quickplay join/leave/accept matchmaking
│   │   │   ├── LobbyTest.php               # Lobby CRUD, ready-check
│   │   │   ├── LobbyPlayerTest.php         # Lobby player invite/respond/kick
│   │   │   ├── BillingTest.php             # Subscriptions across platforms (Stripe, Apple, Google, Telegram)
│   │   │   ├── ProfileTest.php             # Profile show/update
│   │   │   ├── UserStatsTest.php           # Stats retrieval
│   │   │   ├── UserLevelsTest.php          # Level/XP progression
│   │   │   ├── AlertTest.php               # Alert list, mark as read
│   │   │   ├── RematchTest.php             # Rematch request accept/decline
│   │   │   ├── PublicEndpointsTest.php     # Status, titles, rules, leaderboards
│   │   │   └── StripeWebhookTest.php       # Webhook processing
│   ├── Helpers/
│   │   ├── AuthenticationHelper.php         # actingAs(), loginAs(), createToken()
│   │   ├── GameHelper.php                   # createGame(), submitAction(), assertGameState()
│   │   ├── BillingHelper.php                # createSubscription(), verifyReceipt()
│   │   └── AssertionHelper.php              # assertJsonStructure(), assertValidationError()
│   └── Traits/
│       ├── CreatesGames.php                 # Reusable game creation logic
│       ├── CreatesSubscriptions.php         # Reusable subscription setup
│       └── InteractsWithWebSocket.php       # Real-time testing helpers
└── Unit/
    └── [existing unit tests, not part of this feature]
```

**Structure Decision**: Selected web application structure with dedicated `tests/Feature/Api/V1/` directory to mirror API versioning in `routes/api.php`. Test files are organized by controller/feature domain matching the API route groups. Helpers and Traits extracted to reduce duplication and enforce DRY principles across all test files.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

N/A - No constitutional violations. This feature adds test coverage using standard Laravel/Pest patterns.

---

## Phase 0: Outline & Research ✅

**Status**: COMPLETE

**Deliverable**: `research.md`

**Summary**: All research tasks completed with zero NEEDS CLARIFICATION markers. Key technical decisions documented:
- Pest v4 describe() blocks for test organization
- Three-tier helper system (global functions, traits, helper classes)
- RefreshDatabase with transactions (existing configuration)
- Mock broadcasting for real-time testing
- Datasets for multi-platform billing tests
- Custom expectations for JSON structure validation
- Time travel for rate limit testing
- No special performance tuning required initially

All decisions based on existing project infrastructure and Laravel/Pest best practices.

---

## Phase 1: Design & Contracts ✅

**Status**: COMPLETE

**Deliverables**: 
- `data-model.md` - Test data entities and relationships
- `contracts/test-organization.md` - Test suite structure and grouping
- `quickstart.md` - Developer onboarding guide
- `.github/copilot-instructions.md` - Updated agent context

**Summary**: 
- Documented 11 core entities (User, Game, Player, Action, Subscription, Quota, Strike, Lobby, LobbyPlayer, Alert, RematchRequest)
- All entities use existing factories with state methods
- Defined 13 test files covering 40+ endpoints
- Estimated 100-120 total tests with <30s execution time
- Created comprehensive quickstart guide with examples
- Updated Copilot context with PHP 8.3, Pest v4.1, Laravel 12, PostgreSQL

**Post-Design Constitution Check**: ✅ PASS - No new architectural patterns introduced. Standard testing practices maintained.

---

## Next Steps

This plan is now complete. Next phase:

**Phase 2: Task Breakdown** - Run `/speckit.tasks` to generate `tasks.md` with specific implementation tasks broken down by priority and dependencies.
