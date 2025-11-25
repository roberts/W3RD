# Controller Migration Checklist - 008 API Structure

**Migration Strategy**: Direct reorganization, no backward compatibility

## Phase 1: Controller Reorganization

### Step 1: Create Namespace Folders
```bash
mkdir -p app/Http/Controllers/Api/V1/{System,Webhooks,Library,Auth,Account,Floor,Games,Economy,Feeds,Competitions}
```

---

## Controllers by Namespace

### 1. System Namespace
**Folder**: `app/Http/Controllers/Api/V1/System/`

- [ ] **Create** `HealthController.php` (from StatusController)
  - Extract service health check logic
  - Add database, cache, queue, game engine checks
- [ ] **Create** `TimeController.php` (new)
  - Add authoritative server time endpoint
- [ ] **Create** `ConfigController.php` (new)
  - Add global platform configuration endpoint
- [ ] **Delete** `StatusController.php`

---

### 2. Webhooks Namespace
**Folder**: `app/Http/Controllers/Api/V1/Webhooks/`

- [ ] **Create** `WebhookController.php` (consolidate)
  - Extract Stripe webhook logic from StripeWebhookController
  - Add Apple IAP webhook handler
  - Add Google Play webhook handler
  - Add Telegram webhook handler
- [ ] **Delete** `StripeWebhookController.php`

---

### 3. Library Namespace
**Folder**: `app/Http/Controllers/Api/V1/Library/`

- [ ] **Create** `GameLibraryController.php` (from TitleController)
  - Rename and move title browsing logic
  - Add `entities()` method for static game data
- [ ] **Move** `GameRulesController.php` (no changes)
  - Just relocate to namespace folder
- [ ] **Delete** `TitleController.php`

---

### 4. Auth Namespace
**Folder**: `app/Http/Controllers/Api/V1/Auth/`

- [ ] **Move** `AuthController.php` (no changes)
  - Just relocate to namespace folder
  - Keep all methods (register, verify, login, socialLogin, logout, getUser, updateUser)

---

### 5. Account Namespace
**Folder**: `app/Http/Controllers/Api/V1/Account/`

- [ ] **Move** `ProfileController.php` (no changes)
  - Just relocate to namespace folder
- [ ] **Create** `ProgressionController.php` (from UserLevelsController)
  - Rename and move XP/level logic
- [ ] **Create** `RecordsController.php` (from UserStatsController)
  - Rename and move win/loss stats logic
- [ ] **Create** `AlertsController.php` (from AlertController)
  - Rename (plural) and move notification logic
- [ ] **Delete** `UserLevelsController.php`
- [ ] **Delete** `UserStatsController.php`
- [ ] **Delete** `AlertController.php`

---

### 6. Floor Namespace
**Folder**: `app/Http/Controllers/Api/V1/Floor/`

- [ ] **Merge** `LobbyController.php` (combine two controllers)
  - Move existing LobbyController to namespace
  - Merge LobbyPlayerController methods:
    - `store()` → `joinSeat()`
    - `update()` → `updateSeat()`
    - `destroy()` → `leaveSeat()`
- [ ] **Create** `SignalController.php` (from QuickplayController)
  - Rename and move matchmaking logic
- [ ] **Create** `ProposalController.php` (from RematchController)
  - Rename and move challenge/rematch logic
  - Expand to handle both challenges and rematches
- [ ] **Delete** `LobbyPlayerController.php`
- [ ] **Delete** `QuickplayController.php`
- [ ] **Delete** `RematchController.php`

---

### 7. Games Namespace
**Folder**: `app/Http/Controllers/Api/V1/Games/`

- [ ] **Refactor** `GameController.php` (simplify)
  - Keep `index()` and `show()` methods
  - Remove `history()` method → extract to GameTimelineController
  - Remove `forfeit()` method → extract to GameConcedeController
  - Remove `requestRematch()` method → move to Floor/ProposalController
- [ ] **Move** `GameActionController.php` (no changes)
  - Just relocate to namespace folder
  - Rename `options()` route to `/actions/options`
- [ ] **Create** `GameTurnController.php` (new)
  - Add turn timer query endpoint
- [ ] **Create** `GameTimelineController.php` (extract from GameController)
  - Move `history()` method here
  - Rename to `index()` for timeline listing
- [ ] **Create** `GameConcedeController.php` (extract from GameController)
  - Move `forfeit()` method here
  - Rename to `store()` for concede action
- [ ] **Create** `GameAbandonController.php` (new)
  - Add rage quit endpoint
- [ ] **Create** `GameOutcomeController.php` (new)
  - Add final results endpoint

---

### 8. Economy Namespace
**Folder**: `app/Http/Controllers/Api/V1/Economy/`

- [ ] **Create** `BalanceController.php` (new)
  - Add virtual balance query endpoint
- [ ] **Create** `TransactionController.php` (new)
  - Add transaction history endpoint
- [ ] **Create** `CashierController.php` (new)
  - Add balance adjustment endpoint (approved clients only)
- [ ] **Create** `PlanController.php` (from BillingController)
  - Extract `getPlans()` method
  - Rename to `index()`
- [ ] **Create** `ReceiptController.php` (from BillingController)
  - Extract Apple/Google/Telegram verification methods
  - Consolidate into single `verify()` method with provider parameter
- [ ] **Delete** `BillingController.php`

---

### 9. Feeds Namespace
**Folder**: `app/Http/Controllers/Api/V1/Feeds/`

- [ ] **Move** `LeaderboardController.php` (modify)
  - Relocate to namespace folder
  - Convert to SSE stream endpoint
- [ ] **Create** `LiveScoresController.php` (new)
  - Add `games()` SSE endpoint
  - Add `wins()` SSE endpoint
  - Add `tournaments()` SSE endpoint
- [ ] **Create** `CasinoFloorController.php` (new)
  - Add `challenges()` SSE endpoint
  - Add `achievements()` SSE endpoint

---

### 10. Competitions Namespace
**Folder**: `app/Http/Controllers/Api/V1/Competitions/`

- [ ] **Create** `CompetitionController.php` (new)
  - Add `index()` for tournament listing
  - Add `show()` for tournament details
- [ ] **Create** `EntryController.php` (new)
  - Add `store()` for tournament registration
- [ ] **Create** `StructureController.php` (new)
  - Add `show()` for phase rules
- [ ] **Create** `BracketController.php` (new)
  - Add `show()` for bracket visualization
- [ ] **Create** `StandingsController.php` (new)
  - Add `index()` for tournament rankings

---

## Phase 2: Route Restructuring

### Step 1: Backup Current Routes
```bash
cp routes/api.php routes/api.php.backup
```

### Step 2: Rewrite routes/api.php
- [ ] Update all controller imports to new namespace paths
- [ ] Organize routes by namespace groups (System, Webhooks, Library, etc.)
- [ ] Update route prefixes (`/me` → `/account`, `/games/quickplay` → `/floor/signals`, etc.)
- [ ] Remove all old route definitions
- [ ] Test each namespace group

### Step 3: Verify Route List
```bash
php artisan route:list --path=v1
```

---

## Verification Checklist

### Controllers Created
- [ ] 3 System controllers
- [ ] 1 Webhooks controller
- [ ] 2 Library controllers (1 new, 1 moved)
- [ ] 1 Auth controller (moved)
- [ ] 4 Account controllers (1 moved, 3 renamed)
- [ ] 3 Floor controllers (1 merged, 2 renamed)
- [ ] 7 Games controllers (1 refactored, 1 kept, 5 new/extracted)
- [ ] 5 Economy controllers (all new)
- [ ] 3 Feeds controllers (1 moved, 2 new)
- [ ] 5 Competitions controllers (all new)

**Total: 34 controllers across 9 namespaces**

### Controllers Deleted
- [ ] StatusController
- [ ] StripeWebhookController
- [ ] TitleController
- [ ] UserLevelsController
- [ ] UserStatsController
- [ ] AlertController
- [ ] LobbyPlayerController
- [ ] QuickplayController
- [ ] RematchController
- [ ] BillingController

**Total: 10 controllers deleted**

### Routes Updated
- [ ] System namespace (3 routes)
- [ ] Webhooks namespace (4 routes)
- [ ] Library namespace (4 routes)
- [ ] Auth namespace (7 routes)
- [ ] Account namespace (6 routes)
- [ ] Floor namespace (11 routes)
- [ ] Games namespace (8 routes)
- [ ] Economy namespace (5 routes)
- [ ] Feeds namespace (6 routes)
- [ ] Competitions namespace (6 routes)

**Total: 60+ routes organized**

---

## Testing After Migration

### Manual Testing
```bash
# Test each namespace
curl http://localhost/api/v1/system/health
curl http://localhost/api/v1/library
curl http://localhost/api/v1/auth/login -d '...'
# etc.
```

### Automated Testing
```bash
# Run all feature tests
php artisan test --testsuite=Feature

# Run specific namespace tests
php artisan test tests/Feature/Api/V1/System
php artisan test tests/Feature/Api/V1/Floor
php artisan test tests/Feature/Api/V1/Economy
```

---

## Rollback Plan

If issues arise:
```bash
# Restore old routes
cp routes/api.php.backup routes/api.php

# Revert controller changes
git checkout HEAD -- app/Http/Controllers/Api/V1/
```

---

## Documentation Updates After Migration

- [ ] Update OpenAPI specs in `specs/008-api-structure/contracts/`
- [ ] Update README with new endpoint structure
- [ ] Update Postman collection with new URLs
- [ ] Update frontend client SDK with new endpoints
- [ ] Announce breaking changes to API consumers

---

## Timeline Estimate

- **Phase 1 (Controllers)**: 2-3 days
  - Day 1: Create all new namespace folders and controllers
  - Day 2: Extract and merge logic from old controllers
  - Day 3: Delete old controllers, verify compilation

- **Phase 2 (Routes)**: 1-2 days
  - Day 1: Rewrite routes/api.php with new structure
  - Day 2: Test all endpoints manually and with automated tests

- **Total**: 4-5 days for complete migration
