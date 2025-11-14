# Database Implementation - README

## Overview

This document describes the database schema implementation for the GamerProtocol.io API, supporting multi-game gameplay, user management, billing, and gamification.

## Migration Order

Migrations must be run in the following order due to foreign key dependencies:

### Phase 1: Core Identity & Access (001-005)
1. `2025_11_13_000001_create_avatars_table.php` - User avatar assets
2. `2025_11_13_000002_create_agents_table.php` - AI agent profiles
3. `2025_11_13_000003_create_users_table.php` - Central user identity (depends on avatars, agents)
4. `2025_11_13_000004_create_clients_table.php` - API client applications
5. `2025_11_13_000005_create_entries_table.php` - User entry/login tracking (depends on users, clients)

### Phase 2: Game Structure (006-009)
6. `2025_11_13_000007_create_games_table.php` - Game instances with ULID
7. `2025_11_13_000008_create_players_table.php` - Game participants (adds winner_id FK back to games)
8. `2025_11_13_000009_create_actions_table.php` - Action history with validation tracking

### Phase 3: Billing & Quotas (010-011)
10. `2025_11_13_000010_create_strikes_table.php` - Free tier loss tracking
11. `2025_11_13_000011_create_quotas_table.php` - Member tier game limits

### Phase 4: Gamification (012-018)
12. `2025_11_13_000012_create_point_ledgers_table.php` - Point transaction audit trail
13. `2025_11_13_000013_create_global_ranks_table.php` - Leaderboard rankings
14. `2025_11_13_000014_create_badges_table.php` - Achievement definitions
15. `2025_11_13_000015_create_user_badge_table.php` - User badge ownership
16. `2025_11_13_000016_create_user_title_levels_table.php` - Game title-specific levels
17. `2025_11_13_000017_create_user_daily_point_summaries_table.php` - Daily leaderboards
18. `2025_11_13_000018_create_user_monthly_point_summaries_table.php` - Monthly leaderboards

## Model Structure

```
app/Models/
├── Auth/
│   ├── User.php          - Central user model (human & AI)
│   ├── Agent.php         - AI agent extension
│   └── Entry.php         - User entry/login tracking
├── Access/
│   └── Client.php        - API client management
├── Content/
│   └── Avatar.php        - User profile avatars
├── Game/
│   ├── Game.php          - Game instance (uses ULID)
│   ├── Player.php        - Game participants
│   └── Move.php          - Move history
├── Billing/
│   ├── Strike.php        - Free tier tracking
│   └── Quota.php         - Member tier limits
└── Gamification/
    ├── PointLedger.php   - Point transactions
    ├── GlobalRank.php    - Leaderboard data
    ├── Badge.php         - Achievement definitions
    └── UserTitleLevel.php - Game title-specific progression
```

## Key Features

### ULID for Public IDs
- Game model uses ULIDs instead of auto-increment IDs for security
- Prevents enumeration attacks on public-facing routes

### JSON Columns
- `games.game_state` - Flexible board/hand storage
- `moves.move_details` - Game title-specific move data
- `badges.condition_json` - Dynamic unlock criteria

### Composite Primary Keys
- `user_title_levels` - (user_id, title_slug)
- `user_badge` - (user_id, badge_id)
- `strikes` - (user_id, title_slug, strike_date)
- `quotas` - (user_id, title_slug, reset_month)

### Laravel Cashier Integration
- User model includes Stripe billing fields
- Ready for subscription management

### Polymorphic Relationships
- `point_ledgers.source` - Links to games, badges, etc.

## Important Notes

### User Model Location
User model moved from `App\Models\User` to `App\Models\Auth\User` for better organization. A backward compatibility alias exists in `app/Models/User.php`.

### No Down Methods
All migrations omit the `down()` method as per project requirements, focusing on forward-only schema changes.

## Seeders

Initial seed data includes:
- **Game Titles**: Defined as PHP enums (Validate Four, Checkers, Hearts, Spades)
- **Avatars**: 5 free tier avatars
- **Clients**: Web, iOS, Android applications
- **Badges**: First win, 10 wins, 100 wins, 5-win streak

Run seeders with:
```bash
php artisan db:seed
```

## Testing

All migrations have been tested and run successfully. Relationships verified via Laravel Tinker:
- User → Avatar relationship working
- Game title enum casting working
- All models loading correctly
- Seeders populating data successfully

## Database Requirements

- **MySQL 8.0+** (for JSON column support)
- **UTF8MB4** character set
- **EST timezone** for strike/quota calculations (handled in application logic)
