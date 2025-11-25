# Implementation Notes - 008 API Structure

**Updated**: November 20, 2025  
**Status**: Phase 1 & 2 detailed in spec and plan, ready for implementation

## Quick Summary

### What's Happening
Restructuring all API controllers from flat `Api/V1/` directory into 9 logical namespace folders. This organizes 50+ endpoints into cohesive groups (System, Webhooks, Library, Auth, Account, Floor, Games, Economy, Feeds, Competitions).

### Key Decisions Made
✅ **No backward compatibility** - Clean break, delete old routes/controllers  
✅ **Single AuthController** - Keep authentication flows together  
✅ **Merge LobbyController** - Combine lobby + player seat management  
✅ **Delete BillingController** - Split into Economy namespace  
✅ **Extract Game controllers** - Separate Concede, Abandon, Timeline, Outcome  
✅ **Leaderboards → Feeds** - Move to real-time SSE namespace  

### Impact
- **10 controllers deleted** after logic extraction
- **27 new controllers created** across namespaces
- **40+ routes changed** (old URLs deleted, new URLs added)
- **Zero database changes** for Phase 1 & 2

---

## Architecture Decisions

### No Backward Compatibility
- Direct migration from old structure to new namespaces
- Old controllers will be **deleted** after logic extraction
- Old routes will be **deleted** (no aliasing)
- Clean break for production v1 API

### Controller Granularity
- **Single AuthController**: Authentication flows are cohesive (no split)
- **Merged LobbyController**: Lobby + player seat management in one controller
- **No BillingController**: Split into Economy namespace (PlanController + ReceiptController)
- **Extracted Game Controllers**: Break up into dedicated controllers per endpoint
  - GameConcedeController, GameAbandonController, GameTimelineController, GameOutcomeController
- **Leaderboards in Feeds**: SSE-based real-time leaderboard streams

### Namespace Organization
9 logical namespaces:
1. System - Health, time, config
2. Webhooks - External provider callbacks
3. Library - Game discovery
4. Auth - Authentication
5. Account - User profile/progression
6. Floor - Matchmaking coordination
7. Games - Active gameplay
8. Economy - Virtual balance/subscriptions
9. Feeds - Real-time SSE streams
10. Competitions - Tournaments

## Quick Reference

### Version-Controlled Enums (Not Database Tables)

**GameTitle** (`app/Enums/GameTitle.php`) - Existing
- Values: `connect-four`, `checkers`, `hearts`, `spades`
- Referenced via `title_slug` string field in migrations
- No database table - version controlled in code

**MembershipPlan** - To Be Created
- Replaces subscription_plans table concept
- Values: TBD (Free, Pro, Elite suggested)
- Will be referenced via `membership_plan` field in subscription_items

### Database Reference Pattern

All game-related tables use **composite reference**:
```php
$table->string('title_slug', 50)->index();  // References GameTitle enum
$table->foreignId('mode_id')->constrained(); // References modes table
```

Modes table already exists with composite key: `(title_slug, slug)`

## Migration Status

### ✅ Completed (Existing)
- `balances` - 2025_11_20_000001 (multi-client architecture)
- `transactions` - 2025_11_20_000002 (unified virtual + real payments)
- `clients` - Updated with `use_cashier` field
- `tournaments` - 2025_11_20_000003 (competitions with tokens/chips buy-ins)
- `tournament_user` - 2025_11_20_000004 (brackets/participants pivot)
- `plan_audits` - 2025_11_20_000005 (daily strikes + monthly quotas tracking)

### 🔄 To Rename/Modify
1. **rematch_requests → proposals**
   - Add: `type enum(challenge, rematch)`, `title_slug`, `mode_id`, `game_settings`, `responded_at`
   - Rename: `requesting_user_id → sender_id`, `opponent_user_id → recipient_id`, `original_game_id → previous_game_id`
   - Change: `status` from string to enum(pending, accepted, declined, expired, cancelled)

### ➕ To Create New
1. **matchmaking_signals** (Floor namespace)
   - Quickplay/ranked intent tracking with ELO
   - One active signal per user per title+mode
   - Auto-expires after 5 minutes

2. **Add fields to users**
   - Only `stripe_customer_id` (nullable)
   - NOT adding strikes/quotas (now in plan_audits table)

3. **Add fields to games**
   - `final_scores` (json)
   - `xp_awarded` (integer)
   - `rewards` (json)
   - Optional: Convert `outcome_type` from string to enum

4. **Add field to subscription_items** (after MembershipPlan enum created)
   - `membership_plan` (string, references enum)

## Key Architecture Decisions

### No Titles Table
- GameTitle enum provides version control
- More flexible for rapid iteration
- No foreign key constraints to manage
- Values stored as strings in database

### No Subscription Plans Table
- MembershipPlan enum provides version control
- Simplifies plan management
- Aligns with GameTitle pattern

### Simplified Tournament Currency
- **Before**: enum(real_money, bonus_chips, hard_currency)
- **After**: enum(tokens, chips)
- **Rationale**: Aligns with Balance table, entertainment-only economy
- **Default**: chips (typical tournament currency)

### User Economy Fields Location
- `stripe_customer_id` → users table (Cashier integration)
- Daily strikes → plan_audits table (new)
- Monthly quotas → plan_audits table (new)
- **Decision**: Unified strikes/quotas tracking in plan_audits with daily records
- **Timezone**: EST used for day cutoffs in application logic

### PlanAudits Table Design
New table replacing separate strikes/quotas tables:
- **Unique combo**: `(user_id, membership_plan, day)` - one record per user per plan per day
- **Strikes**: Reset daily at midnight EST (new day = new record)
- **Quotas**: Reset monthly on 1st at midnight EST (app logic)
- **Benefits**: Historical tracking, natural reset mechanism, plan changes over time

## Floor Namespace Coordination

New unified matchmaking layer:
1. **MatchmakingSignals** - Quickplay/ranked with ELO matching
2. **Proposals** - Direct challenges + rematches (unified)
3. **Lobbies** - Private rooms (already exists)

## Outstanding Items

### Before Implementation (Phase 1 & 2)
- [ ] Create all namespace folders under `App/Http/Controllers/Api/V1/`
- [ ] Create MembershipPlan enum
- [ ] Define membership tier values (Free, Pro, Elite, etc.)

### Phase 1 - Controller Reorganization
**Controllers to CREATE**:
- [ ] System/HealthController (from StatusController logic)
- [ ] System/TimeController (new)
- [ ] System/ConfigController (new)
- [ ] Webhooks/WebhookController (from StripeWebhookController logic)
- [ ] Library/GameLibraryController (from TitleController logic)
- [ ] Account/ProgressionController (from UserLevelsController logic)
- [ ] Account/RecordsController (from UserStatsController logic)
- [ ] Account/AlertsController (from AlertController logic)
- [ ] Floor/SignalController (from QuickplayController logic)
- [ ] Floor/ProposalController (from RematchController logic)
- [ ] Games/GameTurnController (new)
- [ ] Games/GameTimelineController (extract from GameController)
- [ ] Games/GameConcedeController (extract from GameController)
- [ ] Games/GameAbandonController (new)
- [ ] Games/GameOutcomeController (new)
- [ ] Economy/BalanceController (new)
- [ ] Economy/TransactionController (new)
- [ ] Economy/CashierController (new)
- [ ] Economy/PlanController (from BillingController logic)
- [ ] Economy/ReceiptController (from BillingController logic)
- [ ] Feeds/LiveScoresController (new)
- [ ] Feeds/CasinoFloorController (new)
- [ ] Competitions/CompetitionController (new)
- [ ] Competitions/EntryController (new)
- [ ] Competitions/StructureController (new)
- [ ] Competitions/BracketController (new)
- [ ] Competitions/StandingsController (new)

**Controllers to RELOCATE**:
- [ ] Move AuthController → Auth/AuthController
- [ ] Move ProfileController → Account/ProfileController
- [ ] Move GameRulesController → Library/GameRulesController
- [ ] Move LobbyController → Floor/LobbyController (merge LobbyPlayerController logic)
- [ ] Refactor GameController → Games/GameController (remove extracted methods)
- [ ] Keep GameActionController → Games/GameActionController
- [ ] Move LeaderboardController → Feeds/LeaderboardController

**Controllers to DELETE** (after extraction):
- [ ] ❌ Delete StatusController
- [ ] ❌ Delete StripeWebhookController
- [ ] ❌ Delete TitleController
- [ ] ❌ Delete UserLevelsController
- [ ] ❌ Delete UserStatsController
- [ ] ❌ Delete AlertController
- [ ] ❌ Delete LobbyPlayerController
- [ ] ❌ Delete QuickplayController
- [ ] ❌ Delete RematchController
- [ ] ❌ Delete BillingController

### Phase 2 - Route Restructuring
- [ ] Rewrite `routes/api.php` with namespace organization
- [ ] Update all route imports for new controller locations
- [ ] Delete old route definitions (no backward compatibility)
- [ ] Test all endpoints with new URLs

### After Phase 1 & 2
- [ ] Update tests to match new controller namespaces
- [ ] Update API documentation
- [ ] Create MatchmakingSignals migration
- [ ] Rename rematch_requests to proposals migration
- [ ] Add stripe_customer_id to users
- [ ] Add outcome fields to games
- [ ] Add membership_plan to subscription_items (after enum)

## Testing Notes

All new namespaces need:
- Feature tests for CRUD operations
- Integration tests for relationships
- Validation tests for business rules (e.g., max 5 pending proposals)
- Test structure mirrors controller namespaces:
  - `tests/Feature/Api/V1/System/`
  - `tests/Feature/Api/V1/Webhooks/`
  - `tests/Feature/Api/V1/Library/`
  - `tests/Feature/Api/V1/Auth/`
  - `tests/Feature/Api/V1/Account/`
  - `tests/Feature/Api/V1/Floor/`
  - `tests/Feature/Api/V1/Games/`
  - `tests/Feature/Api/V1/Economy/`
  - `tests/Feature/Api/V1/Feeds/`
  - `tests/Feature/Api/V1/Competitions/`

## Route Transformation Reference

### Old → New URL Mappings

**System & Infrastructure**:
- ❌ `GET /v1/status` → ✅ `GET /v1/system/health`
- ➕ `GET /v1/system/time` (new)
- ➕ `GET /v1/system/config` (new)

**Webhooks**:
- ❌ `POST /v1/stripe/webhook` → ✅ `POST /v1/webhooks/stripe`
- ➕ `POST /v1/webhooks/apple` (new)
- ➕ `POST /v1/webhooks/google` (new)
- ➕ `POST /v1/webhooks/telegram` (new)

**Library**:
- ❌ `GET /v1/titles` → ✅ `GET /v1/library`
- ❌ `GET /v1/titles/{gameTitle}/rules` → ✅ `GET /v1/library/{key}/rules`
- ➕ `GET /v1/library/{key}` (new)
- ➕ `GET /v1/library/{key}/entities` (new)

**Auth** (no changes to URLs):
- ✅ `POST /v1/auth/register`
- ✅ `POST /v1/auth/verify`
- ✅ `POST /v1/auth/login`
- ✅ `POST /v1/auth/social`
- ✅ `POST /v1/auth/logout`
- ✅ `GET /v1/auth/user`
- ✅ `PATCH /v1/auth/user`

**Account** (was `/v1/me/*`):
- ❌ `GET /v1/me/profile` → ✅ `GET /v1/account/profile`
- ❌ `PATCH /v1/me/profile` → ✅ `PATCH /v1/account/profile`
- ❌ `GET /v1/me/stats` → ✅ `GET /v1/account/records`
- ❌ `GET /v1/me/levels` → ✅ `GET /v1/account/progression`
- ❌ `GET /v1/me/alerts` → ✅ `GET /v1/account/alerts`
- ❌ `POST /v1/me/alerts/mark-as-read` → ✅ `POST /v1/account/alerts/read`

**Floor** (was scattered in `/v1/games/*`):
- ❌ `GET /v1/games/lobbies` → ✅ `GET /v1/floor/lobbies`
- ❌ `POST /v1/games/lobbies` → ✅ `POST /v1/floor/lobbies`
- ❌ `GET /v1/games/lobbies/{ulid}` → ✅ `GET /v1/floor/lobbies/{ulid}`
- ❌ `DELETE /v1/games/lobbies/{ulid}` → ✅ `DELETE /v1/floor/lobbies/{ulid}`
- ❌ `POST /v1/games/lobbies/{ulid}/ready-check` → ✅ `POST /v1/floor/lobbies/{ulid}/ready-check`
- ❌ `POST /v1/games/lobbies/{ulid}/players` → ✅ `POST /v1/floor/lobbies/{ulid}/seat`
- ❌ `PUT /v1/games/lobbies/{ulid}/players/{username}` → ✅ `PUT /v1/floor/lobbies/{ulid}/seat/{position}`
- ❌ `DELETE /v1/games/lobbies/{ulid}/players/{username}` → ✅ `DELETE /v1/floor/lobbies/{ulid}/seat`
- ❌ `POST /v1/games/quickplay` → ✅ `POST /v1/floor/signals`
- ❌ `DELETE /v1/games/quickplay` → ✅ `DELETE /v1/floor/signals/{ulid}`
- ❌ `POST /v1/games/quickplay/accept` → (removed, matchmaking auto-accepts)
- ❌ `POST /v1/games/rematch/{requestId}/accept` → ✅ `POST /v1/floor/proposals/{ulid}/accept`
- ❌ `POST /v1/games/rematch/{requestId}/decline` → ✅ `POST /v1/floor/proposals/{ulid}/decline`
- ❌ `POST /v1/games/{gameUlid}/rematch` → ✅ `POST /v1/floor/proposals` (with type=rematch)

**Games** (cleaned up):
- ✅ `GET /v1/games` (no change)
- ✅ `GET /v1/games/{ulid}` (no change)
- ❌ `POST /v1/games/{ulid}/action` → ✅ `POST /v1/games/{ulid}/actions`
- ❌ `GET /v1/games/{ulid}/options` → ✅ `GET /v1/games/{ulid}/actions/options`
- ➕ `GET /v1/games/{ulid}/turn` (new)
- ❌ `GET /v1/games/{ulid}/history` → ✅ `GET /v1/games/{ulid}/timeline`
- ❌ `POST /v1/games/{ulid}/forfeit` → ✅ `POST /v1/games/{ulid}/concede`
- ➕ `POST /v1/games/{ulid}/abandon` (new)
- ➕ `GET /v1/games/{ulid}/outcome` (new)

**Economy** (was `/v1/billing/*`):
- ➕ `GET /v1/economy/balance` (new)
- ➕ `GET /v1/economy/transactions` (new)
- ➕ `POST /v1/economy/cashier` (new)
- ❌ `GET /v1/billing/plans` → ✅ `GET /v1/economy/plans`
- ❌ `GET /v1/billing/status` → (removed, check balance instead)
- ❌ `POST /v1/billing/subscribe` → (handled via webhooks)
- ❌ `GET /v1/billing/manage` → (handled via Stripe portal)
- ❌ `POST /v1/billing/apple/verify` → ✅ `POST /v1/economy/receipts/apple`
- ❌ `POST /v1/billing/google/verify` → ✅ `POST /v1/economy/receipts/google`
- ❌ `POST /v1/billing/telegram/verify` → ✅ `POST /v1/economy/receipts/telegram`

**Feeds** (mostly new):
- ❌ `GET /v1/leaderboard/{gameTitle}` → ✅ `GET /v1/feeds/leaderboards` (SSE)
- ➕ `GET /v1/feeds/games` (new SSE)
- ➕ `GET /v1/feeds/wins` (new SSE)
- ➕ `GET /v1/feeds/tournaments` (new SSE)
- ➕ `GET /v1/feeds/challenges` (new SSE)
- ➕ `GET /v1/feeds/achievements` (new SSE)

**Competitions** (all new):
- ➕ `GET /v1/competitions` (new)
- ➕ `GET /v1/competitions/{ulid}` (new)
- ➕ `POST /v1/competitions/{ulid}/enter` (new)
- ➕ `GET /v1/competitions/{ulid}/structure` (new)
- ➕ `GET /v1/competitions/{ulid}/bracket` (new)
- ➕ `GET /v1/competitions/{ulid}/standings` (new)

**Legend**:
- ❌ Old route (will be deleted)
- ✅ New route
- ➕ Brand new endpoint

## Testing Notes

All new tables need:
- Factory definitions
- Feature tests for CRUD operations
- Integration tests for relationships
- Validation tests for business rules (e.g., max 5 pending proposals)

## Related Documents
- [plan.md](./plan.md) - Full implementation plan
- [data-model.md](./data-model.md) - Complete entity specifications with migrations
- [spec.md](./spec.md) - Original feature specification
