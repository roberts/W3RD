# Implementation Notes - 008 API Structure

**Updated**: November 20, 2025  
**Status**: Spec clarifications completed, ready for implementation

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

### Before Implementation
- [ ] Create `MembershipPlan` enum
- [ ] Define membership tier values (Free, Pro, Elite, etc.)
- [ ] Decide if outcome_type conversion to enum is needed

### During Implementation
- [ ] Rename rematch_requests migration/model to proposals
- [ ] Create matchmaking_signals migration
- [x] Create tournaments + tournament_user migrations
- [x] Create plan_audits migration (replaces strikes/quotas on users)
- [ ] Add stripe_customer_id to users
- [ ] Add outcome fields to games
- [ ] Add membership_plan to subscription_items (after enum)

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
