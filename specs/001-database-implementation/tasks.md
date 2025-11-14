# Implementation Tasks: Database Schema & Models

**Feature**: Database Implementation for GamerProtocol.io API  
**Branch**: `001-database-implementation`  
**Generated**: November 13, 2025

---

## Overview

This document contains all tasks for implementing the complete database schema and Eloquent models for the GamerProtocol.io API. The implementation is organized into logical phases that can be completed incrementally, with each phase building upon the previous.

**Total Tasks**: 71  
**Phases**: 6  
**Parallel Opportunities**: 35 tasks marked with [P]

---

## Phase 1: Setup & Configuration (6 tasks)

**Goal**: Prepare the Laravel environment for database implementation.

### Tasks

- [ ] T001 Verify Laravel 12 is installed and configured in composer.json
- [ ] T002 Verify MySQL 8.0+ connection in config/database.php with JSON support enabled
- [ ] T003 [P] Install and configure Laravel Sanctum for API authentication
- [ ] T004 [P] Install and configure Laravel Cashier for Stripe billing
- [ ] T005 [P] Install and configure Laravel Reverb for WebSocket support
- [ ] T006 Create database/migrations directory structure if not exists

**Completion Criteria**: All dependencies installed, database connection verified, ready for migrations.

---

## Phase 2: Core Identity & Access Layer (18 tasks)

**Goal**: Establish foundational tables for users, agents, authentication, and API access.

**Story**: [US1] As a system, I need a unified user identity system that supports both human players and AI agents.

### Migration Tasks

- [ ] T007 [P] [US1] Create migration database/migrations/2025_11_13_000001_create_avatars_table.php
- [ ] T008 [P] [US1] Create migration database/migrations/2025_11_13_000002_create_agents_table.php
- [ ] T009 [US1] Create migration database/migrations/2025_11_13_000003_create_users_table.php with Cashier fields
- [ ] T010 [P] [US1] Create migration database/migrations/2025_11_13_000004_create_clients_table.php
- [ ] T011 [US1] Create migration database/migrations/2025_11_13_000005_create_sessions_table.php

### Model Tasks

- [ ] T012 [P] [US1] Create Avatar model in app/Models/Content/Avatar.php with relationships
- [ ] T013 [P] [US1] Create Agent model in app/Models/Auth/Agent.php with relationships
- [ ] T014 [US1] Update User model in app/Models/User.php - add Sanctum, Cashier, Billable traits
- [ ] T015 [US1] Add User model relationships (avatar, agent, sessions, players)
- [ ] T016 [US1] Add User model casts (email_verified_at, deactivated_at as datetime)
- [ ] T017 [US1] Add User helper methods (isAgent(), isActive())
- [ ] T018 [P] [US1] Create Client model in app/Models/Access/Client.php with relationships
- [ ] T019 [P] [US1] Create Session model in app/Models/Auth/Session.php with relationships

### Migration Execution

- [ ] T020 [US1] Run migrations for avatars, agents tables
- [ ] T021 [US1] Run migration for users table (depends on avatars, agents)
- [ ] T022 [US1] Run migrations for clients, sessions tables (depends on users)
- [ ] T023 [US1] Verify all foreign key constraints are properly created

### Model Organization

- [ ] T024 [US1] Move User model to app/Models/Auth/User.php and update namespace
- [ ] T025 [US1] Update all User model imports throughout the application
- [ ] T026 [US1] Update config/auth.php to reference new User model location

**Completion Criteria**: 
- 5 tables created (avatars, agents, users, clients, sessions)
- 5 models with proper relationships and casts
- All foreign keys working
- User model supports both human and agent profiles

---

## Phase 3: Game Structure (14 tasks)

**Goal**: Create tables and models for game definitions, matches, players, and moves.

**Story**: [US2] As a system, I need to store flexible game state for multiple game types without schema changes.

### Migration Tasks

- [ ] T027 [P] [US2] Create migration database/migrations/2025_11_13_000006_create_games_table.php
- [ ] T028 [US2] Create migration database/migrations/2025_11_13_000007_create_matches_table.php with ULID and JSON game_state
- [ ] T029 [US2] Create migration database/migrations/2025_11_13_000008_create_players_table.php with winner_id FK addition
- [ ] T030 [P] [US2] Create migration database/migrations/2025_11_13_000009_create_moves_table.php

### Model Tasks

- [ ] T031 [P] [US2] Create Game model in app/Models/Game/Game.php with relationships
- [ ] T032 [US2] Create Match model in app/Models/Match/Match.php with HasUlids trait
- [ ] T033 [US2] Add Match model casts (game_state as array, turn_number as integer)
- [ ] T034 [US2] Add Match model relationships (game, creator, players, winner, moves)
- [ ] T035 [US2] Override getRouteKeyName() in Match model to use 'ulid'
- [ ] T036 [US2] Add Match helper methods (isFinished(), isActive())
- [ ] T037 [P] [US2] Create Player model in app/Models/Match/Player.php with relationships
- [ ] T038 [P] [US2] Create Move model in app/Models/Match/Move.php with JSON cast

### Migration Execution

- [ ] T039 [US2] Run migrations for games, matches tables
- [ ] T040 [US2] Run migrations for players table (adds winner_id FK to matches)
- [ ] T041 [US2] Run migration for moves table
- [ ] T042 [US2] Verify ULID generation works on Match model

**Completion Criteria**:
- 4 tables created (games, matches, players, moves)
- 4 models with proper relationships
- ULID working for match public IDs
- JSON casting working for game_state and move_details

---

## Phase 4: Billing & Quota System (8 tasks)

**Goal**: Implement usage tracking for free tier (strikes) and member tier (quotas).

**Story**: [US3] As a system, I need to enforce usage limits based on subscription tiers.

### Migration Tasks

- [ ] T043 [P] [US3] Create migration database/migrations/2025_11_13_000010_create_strikes_table.php
- [ ] T044 [P] [US3] Create migration database/migrations/2025_11_13_000011_create_quotas_table.php

### Model Tasks

- [ ] T045 [P] [US3] Create Strike model in app/Models/Billing/Strike.php with user relationship
- [ ] T046 [P] [US3] Create Quota model in app/Models/Billing/Quota.php with user relationship
- [ ] T047 [US3] Add strikes() relationship to User model
- [ ] T048 [US3] Add quotas() relationship to User model

### Migration Execution

- [ ] T049 [US3] Run migrations for strikes and quotas tables
- [ ] T050 [US3] Verify unique constraints on (user_id, game_slug, date/month)

**Completion Criteria**:
- 2 tables created (strikes, quotas)
- 2 models with proper casts and relationships
- Unique constraints preventing duplicate records
- Ready for billing service implementation

---

## Phase 5: Gamification System (18 tasks)

**Goal**: Implement points, ranks, badges, and level progression system.

**Story**: [US4] As a system, I need to track user progression and achievements across all games.

### Migration Tasks

- [ ] T051 [P] [US4] Create migration database/migrations/2025_11_13_000012_create_point_ledgers_table.php
- [ ] T052 [P] [US4] Create migration database/migrations/2025_11_13_000013_create_global_ranks_table.php
- [ ] T053 [P] [US4] Create migration database/migrations/2025_11_13_000014_create_badges_table.php
- [ ] T054 [P] [US4] Create migration database/migrations/2025_11_13_000015_create_user_badge_table.php
- [ ] T055 [P] [US4] Create migration database/migrations/2025_11_13_000016_create_user_game_levels_table.php
- [ ] T056 [P] [US4] Create migration database/migrations/2025_11_13_000017_create_user_daily_point_summaries_table.php
- [ ] T057 [P] [US4] Create migration database/migrations/2025_11_13_000018_create_user_monthly_point_summaries_table.php

### Model Tasks

- [ ] T058 [P] [US4] Create PointLedger model in app/Models/Gamification/PointLedger.php with polymorphic source
- [ ] T059 [P] [US4] Create GlobalRank model in app/Models/Gamification/GlobalRank.php with custom primary key
- [ ] T060 [P] [US4] Create Badge model in app/Models/Gamification/Badge.php with condition_json cast
- [ ] T061 [P] [US4] Create UserGameLevel model in app/Models/Gamification/UserGameLevel.php with composite key
- [ ] T062 [US4] Add gamification relationships to User model (pointLedgers, globalRank, badges, gameLevels)

### Migration Execution

- [ ] T063 [US4] Run migrations for point_ledgers, global_ranks, badges tables
- [ ] T064 [US4] Run migrations for user_badge, user_game_levels tables
- [ ] T065 [US4] Run migrations for daily and monthly point summaries
- [ ] T066 [US4] Verify polymorphic relationships work on PointLedger
- [ ] T067 [US4] Verify composite primary keys work on UserGameLevel
- [ ] T068 [US4] Verify belongsToMany with pivot works for User badges

**Completion Criteria**:
- 7 tables created (point_ledgers, global_ranks, badges, user_badge, user_game_levels, summaries)
- 4 models with proper relationships and casts
- Polymorphic source working on PointLedger
- Many-to-many badges relationship working

---

## Phase 6: Seeding & Validation (5 tasks)

**Goal**: Populate initial data and validate the complete database schema.

### Seeder Tasks

- [ ] T069 Create database/seeders/GameSeeder.php for initial games (validate-four, checkers, hearts, spades)
- [ ] T070 Create database/seeders/AvatarSeeder.php for free tier avatars
- [ ] T071 Create database/seeders/ClientSeeder.php for web, ios, android clients
- [ ] T072 Create database/seeders/BadgeSeeder.php for initial achievement definitions
- [ ] T073 Update database/seeders/DatabaseSeeder.php to call all seeders

### Validation Tasks

- [ ] T074 Run all seeders and verify data is created correctly
- [ ] T075 Test User model in tinker: create user, assign avatar, check relationships
- [ ] T076 Test Match model in tinker: create match with ULID, add players, verify game_state JSON
- [ ] T077 Test Agent creation in tinker: create agent profile, link to user, verify isAgent()
- [ ] T078 Generate database schema diagram documentation
- [ ] T079 Create database/migrations/README.md documenting migration order and dependencies

**Completion Criteria**:
- All tables seeded with initial data
- All model relationships tested and working
- Database schema fully validated
- Documentation complete

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 1 (Setup)
    ↓
Phase 2 (Identity & Access) ← BLOCKING
    ↓
Phase 3 (Game Structure)
    ↓
Phase 4 (Billing) ← Can run parallel with Phase 5
    ↓
Phase 5 (Gamification)
    ↓
Phase 6 (Seeding & Validation)
```

### Migration Execution Order

**CRITICAL**: Migrations must run in numerical order (T007-T057) due to foreign key dependencies.

**Safe Parallel Execution**: Models can be created in parallel once their tables exist:
- After T022: Can create all Phase 2 models in parallel (T012-T019)
- After T041: Can create all Phase 3 models in parallel (T031, T037, T038)
- After T050: Can create Phase 4 models in parallel (T045-T046)
- After T065: Can create Phase 5 models in parallel (T058-T061)

---

## Parallel Execution Opportunities

### Phase 2: Core Identity (10 parallel tasks)
- T007, T008, T010 (independent table migrations)
- T012, T013, T018, T019 (model creation after migrations)

### Phase 3: Game Structure (4 parallel tasks)
- T027, T030 (independent migrations)
- T031, T037, T038 (model creation)

### Phase 4: Billing (4 parallel tasks)
- T043, T044 (both migrations)
- T045, T046 (both models)

### Phase 5: Gamification (11 parallel tasks)
- T051-T057 (all 7 migrations are independent)
- T058-T061 (all 4 models after migrations)

**Total Parallel Tasks**: 29 out of 79 tasks (37%)

---

## Testing Strategy

Since this is database schema implementation, testing will focus on:

1. **Migration Testing**:
   - Each migration runs without errors
   - Foreign keys are properly created
   - Indexes are in place
   - Unique constraints work

2. **Model Testing**:
   - Relationships load correctly
   - Casts work as expected (JSON, datetime, ULID)
   - Helper methods return correct values

3. **Integration Testing**:
   - Create complete game flow: User → Match → Players → Moves
   - Create agent: Agent → User → Match
   - Test gamification: User → PointLedger → GlobalRank

**Testing will be manual via tinker** as specified in Phase 6 validation tasks.

---

## Implementation Strategy

### MVP Scope (Minimum Viable Product)
- **Phase 1**: Setup ✓
- **Phase 2**: Core Identity ✓
- **Phase 3**: Game Structure ✓

This MVP allows basic game functionality with users and matches.

### Incremental Delivery
1. **First Increment**: Phases 1-3 (Core gameplay)
2. **Second Increment**: Phase 4 (Billing)
3. **Third Increment**: Phase 5 (Gamification)
4. **Final Increment**: Phase 6 (Polish)

### Rollback Strategy
Since migrations have no `down()` methods as requested, rollback requires manual database cleanup if needed. Document any rollback procedures in database/migrations/README.md.

---

## Notes

- All migrations omit `down()` methods as specified in requirements
- ULIDs are used for Match public identifiers (security best practice)
- JSON columns require MySQL 8.0+ 
- Composite primary keys used where appropriate for natural uniqueness
- Strategic indexing for performance on high-query columns
- EST timezone critical for strikes/quotas calculations (implement in application logic)

---

## Completion Checklist

### Phase 1: Setup
- [ ] All dependencies installed
- [ ] Database connection verified

### Phase 2: Core Identity
- [ ] 5 migrations created and run
- [ ] 5 models created with relationships
- [ ] User model moved to Auth namespace
- [ ] All imports updated

### Phase 3: Game Structure  
- [ ] 4 migrations created and run
- [ ] 4 models created with relationships
- [ ] ULID generation verified
- [ ] JSON casting verified

### Phase 4: Billing
- [ ] 2 migrations created and run
- [ ] 2 models created with relationships
- [ ] Unique constraints verified

### Phase 5: Gamification
- [ ] 7 migrations created and run
- [ ] 4 models created with relationships
- [ ] Polymorphic relationships verified
- [ ] Composite keys verified

### Phase 6: Seeding & Validation
- [ ] 4 seeders created
- [ ] All data seeded
- [ ] All relationships tested in tinker
- [ ] Documentation complete

---

**Generated**: November 13, 2025  
**Ready for Implementation**: ✓
