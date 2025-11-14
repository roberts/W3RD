# Feature Specification: Database Schema & Models Implementation

**Feature ID**: 001  
**Feature Name**: Database Implementation  
**Priority**: P0 (Foundation)  
**Date**: November 13, 2025

---

## Overview

Implement the complete database schema and Eloquent models for the GamerProtocol.io API. This is the foundational layer that enables all other features including authentication, gameplay, billing, and gamification.

---

## User Stories

### [US1] Core Identity & Access Layer
**Priority**: P1 (Critical - Blocking)  
**As a** system architect  
**I want** a unified user identity system that supports both human players and AI agents  
**So that** the platform can treat all players consistently while maintaining agent-specific configurations

**Acceptance Criteria**:
- Users table supports both human and AI profiles via agent_id foreign key
- Agents table stores AI-specific data (logic path, availability hours)
- Avatars can be assigned to any user
- Clients table manages API keys for different frontend applications
- Entries table tracks user logins/access entries across different client frontends
- All relationships properly defined and testable

**Technical Requirements**:
- Laravel Sanctum integration for user authentication
- Laravel Cashier fields for Stripe billing
- Foreign key constraints properly enforced
- Deactivation support (soft deactivation via timestamp)

---

### [US2] Game Structure & Flexible State
**Priority**: P1 (Critical - Blocking)  
**As a** game developer  
**I want** a flexible game state storage system using JSON columns  
**So that** new game titles can be added without schema migrations

**Acceptance Criteria**:
- GameTitle enum defines available game titles (validate-four, checkers, hearts, spades)
- Games table stores individual game instances using ULIDs for public identification
- game_state JSON column stores board/hand state for any game title
- Players table links games to users (simplified from polymorphic)
- Actions table records complete game history with validation tracking
- Winner determined via player_id foreign key

**Technical Requirements**:
- ULID support for secure public IDs
- JSON casting for game_state and action_details
- Proper indexing on frequently queried columns (title_slug, status, action_type)
- Support for game status workflow (pending → active → finished)
- Action validation tracking (status: success/invalid/error)

---

### [US3] Billing & Usage Limits
**Priority**: P2 (High)  
**As a** business owner  
**I want** to enforce usage limits based on subscription tiers  
**So that** free users are limited to 3 losses per game title per day and members get 2000 games per game title per month

**Acceptance Criteria**:
- Strikes table tracks daily losses per game title per user (EST timezone)
- Quotas table tracks monthly games per game title per user (EST timezone)
- Unique constraints prevent duplicate records
- Ready for billing service to query and enforce limits

**Technical Requirements**:
- Composite unique keys on (user_id, title_slug, date/month)
- Date handling for EST timezone calculations
- Integration with Laravel Cashier subscription status

---

### [US4] Gamification & Progression
**Priority**: P2 (High)  
**As a** product manager  
**I want** a comprehensive gamification system  
**So that** users are engaged through points, levels, badges, and leaderboards

**Acceptance Criteria**:
- PointLedger provides immutable audit trail for all points
- GlobalRank caches total points for fast leaderboard queries
- Badges define achievement criteria in JSON
- UserTitleLevel tracks game title-specific skill progression
- Daily and monthly summaries enable historical leaderboards
- Level decay ready to be implemented via last_played_at timestamp

**Technical Requirements**:
- Polymorphic relationship for point sources (games, badges, etc)
- Composite primary keys where appropriate
- JSON casting for badge conditions
- Strategic indexing for leaderboard queries
- Many-to-many relationship for user badges with earned_at pivot

---

## Dependencies

```
[US1] Core Identity (BLOCKING)
    ↓
[US2] Game Structure (depends on users)
    ↓
[US3] Billing ← Can parallel with US4
[US4] Gamification
```

---

## Technical Constraints

1. **MySQL 8.0+ Required**: JSON column support mandatory
2. **No Down Methods**: Migrations are forward-only as specified
3. **EST Timezone**: All date-based calculations (strikes, quotas) use EST
4. **Foreign Key Order**: Migrations must run in specific sequence
5. **Naming Convention**: `clients` table (not `interfaces`) with `X-Client-Key` header

---

## Out of Scope

The following are explicitly excluded from this feature:
- ❌ Service layer implementation
- ❌ API endpoint controllers
- ❌ Matchmaking Redis logic
- ❌ Agent AI strategies
- ❌ Reverb WebSocket setup
- ❌ Admin panel (Filament)
- ❌ Any expansion ideas from expansion.md

---

## Success Metrics

- ✅ 18 migrations created and executed successfully
- ✅ 15 Eloquent models with full relationships
- ✅ All foreign key constraints working
- ✅ ULID generation working for games
- ✅ JSON casting working for flexible fields
- ✅ All relationships testable in tinker
- ✅ Initial seed data loaded

---

## Testing Strategy

**Manual Testing via Tinker**:
1. Create users (human and agent)
2. Create games with ULIDs
3. Add players to games
4. Record actions with JSON data and validation tracking
5. Test all model relationships
6. Verify constraints and cascades

**No automated tests** are required for this database-only feature.

---

## Implementation Notes

- Migrations numbered sequentially from 2025_11_13_000001 through 000018
- Models organized by domain (Auth, Access, Content, Game, Billing, Gamification)
- User model requires namespace move from app/Models to app/Models/Auth
- All imports must be updated after User model move
- Strategic use of parallel execution opportunities (37% of tasks)

---

## References

- Main specification: `.speckit/specification.md`
- Database docs: `docs/database.md`
- Model docs: `docs/models.md`
- API docs: `docs/api.md`
- Implementation plan: `specs/001-database-implementation/plan.md`
