# GamerProtocol.io Database Implementation Plan

**Date**: November 13, 2025  
**Project**: Laravel 12 Multi-Game Platform API  
**Focus**: Optimized migrations and Eloquent models (excluding expansion ideas)

---

## Executive Summary

This plan details the complete database schema and model structure for the GamerProtocol.io API, supporting:
- **Multi-game support** (Validate Four, Checkers, Hearts, Spades)
- **Flexible user types** (Human players and AI agents)
- **Usage-based billing** (Strikes for free tier, Quotas for member tier)
- **Gamification** (Points, Levels, Badges, Leaderboards)
- **Real-time gameplay** via Laravel Reverb

All migrations are optimized without `down()` methods as requested.

---

## Technical Context

**Language/Version**: PHP 8.2+ / Laravel 12  
**Primary Dependencies**: 
- `laravel/sanctum` (API authentication)
- `laravel/cashier` (Stripe subscriptions)
- `laravel/reverb` (WebSocket real-time)
- `guzzlehttp/guzzle` (External API calls)

**Database**: MySQL 8.0+ (JSON column type support required)  
**Testing**: Pest PHP  
**Storage**: JSON columns for flexible game state  
**Performance Goals**: <200ms API response time, support for 10k+ concurrent users  
**Constraints**: EST timezone for all quota/strike calculations

---

## Migration Order & Dependencies

Migrations must be run in this specific order due to foreign key dependencies:

### Phase 1: Core Identity & Access (001-005)
1. `avatars` - Standalone, no dependencies
2. `agents` - Standalone, no dependencies  
3. `users` - Depends on: avatars, agents
4. `clients` - Standalone, no dependencies
5. `sessions` - Depends on: users, clients

### Phase 2: Game Structure (006-009)
6. `games` - Standalone, no dependencies
7. `matches` - Depends on: users (partial, winner_id added later)
8. `players` - Depends on: matches, users (also adds winner_id FK to matches)
9. `moves` - Depends on: matches, players

### Phase 3: Billing & Quotas (010-011)
10. `strikes` - Depends on: users
11. `quotas` - Depends on: users

### Phase 4: Gamification (012-017)
12. `point_ledgers` - Depends on: users (polymorphic source)
13. `global_ranks` - Depends on: users
14. `badges` - Standalone, no dependencies
15. `user_badge` - Depends on: users, badges
16. `user_title_levels` - Depends on: users
17. `user_daily_point_summaries` - Depends on: users
18. `user_monthly_point_summaries` - Depends on: users

---

## Phase 1: Core Identity & Access Layer

### Migration 001: `create_avatars_table`

**File**: `database/migrations/2025_11_13_000001_create_avatars_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avatars', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->foreignId('image_id')->nullable()->constrained('images');
            $table->enum('type', ['free', 'premium', 'nft'])->default('free');
            $table->timestamps();
        });
    }
};
```

**Purpose**: Reusable identity assets for user profiles  
**Key Features**:
- Unique name constraint
- Type categorization (free/premium/nft)
- Foreign key to images table from drewroberts/media package

---

### Migration 002: `create_agents_table`

**File**: `database/migrations/2025_11_13_000002_create_agents_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('ai_logic_path');
            $table->tinyInteger('available_hour_est')->nullable();
            $table->timestamps();
        });
    }
};
```

**Purpose**: AI agent profile data (extends User model)  
**Key Features**:
- `ai_logic_path`: Fully qualified class name for AI strategy
- `available_hour_est`: Hour (0-23) when agent is available for matchmaking

---

### Migration 003: `create_users_table`

**File**: `database/migrations/2025_11_13_000003_create_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('agent_id')->nullable()->unique()->constrained('agents');
            $table->rememberToken();
            $table->timestamps();
            
            // Foreign key for avatar
            $table->foreignId('avatar_id')->nullable()->constrained('avatars');
            
            // Laravel Cashier fields
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            
            // Deactivation field
            $table->timestamp('deactivated_at')->nullable()->index();
        });

        // Password reset tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
```

**Purpose**: Central identity table for all users (human and AI)  
**Key Features**:
- `username`: Unique handle for logins/mentions
- `agent_id`: Links to agent profile (nullable, unique constraint means 1:1)
- `avatar_id`: Selected avatar asset
- Cashier billing fields for Stripe integration
- `deactivated_at`: Soft account deactivation timestamp
- Includes password reset tokens table

---

### Migration 004: `create_clients_table`

**File**: `database/migrations/2025_11_13_000004_create_clients_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('api_key', 64)->unique();
            $table->enum('platform', ['web', 'ios', 'android', 'electron', 'cli']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
```

**Purpose**: Frontend application API key management  
**Key Features**:
- `api_key`: Unique 64-character key for X-Client-Key header
- `platform`: Categorization of client applications
- `is_active`: Enable/disable specific clients

---

### Migration 005: `create_entries_table`

**File**: `database/migrations/2025_11_13_000005_create_entries_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('client_id')->constrained('clients');
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info', 512)->nullable();
            $table->string('token_id', 100)->nullable();
            $table->timestamp('logged_in_at')->useCurrent();
            $table->timestamp('logged_out_at')->nullable();
        });
    }
};
```

**Purpose**: Tracks user entries (login sessions) to the GamerProtocol platform through any client frontend. This documents frontend access and helps with security monitoring and usage analytics.  
**Key Features**:
- Links user to specific client application
- Tracks IP address and device information
- `token_id`: Reference to Sanctum token
- Login/logout timestamps for analytics

---
## Phase 2: Game Structure

### Migration 006: `create_titles_table`

**File**: `database/migrations/2025_11_13_000006_create_titles_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name');
            $table->integer('max_players')->default(2);
            $table->timestamps();
        });
    }
};
```

**Purpose**: Game type definitions/blueprints  
**Key Features**:
- `slug`: Unique identifier (e.g., 'validate-four', 'checkers')
- `max_players`: Maximum players per match

---

### Migration 007: `create_games_table`

**File**: `database/migrations/2025_11_13_000007_create_games_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique()->index();
            $table->string('title_slug', 50)->index();
            $table->enum('status', ['pending', 'active', 'finished'])->default('pending');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users');
            $table->unsignedBigInteger('winner_id')->nullable();
            $table->integer('turn_number')->default(0);
            $table->json('game_state');
            $table->timestamps();
        });
    }
};
```

**Purpose**: Individual game instances  
**Key Features**:
- `ulid`: Public-facing unique identifier (more secure than auto-increment ID)
- `game_state`: JSON column for flexible game board/hand storage
- `winner_id`: Foreign key added in next migration after players table exists
- Status tracking: pending → active → finished

---

### Migration 008: `create_players_table`

**File**: `database/migrations/2025_11_13_000008_create_players_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('matches');
            $table->foreignId('user_id')->constrained('users');
            $table->string('name', 50);
            $table->tinyInteger('position_id')->comment('Turn order: 1, 2, 3, 4');
            $table->string('color', 20);
            
            $table->unique(['game_id', 'position_id']);
            $table->timestamps();
        });
        
        // Add winner_id foreign key to matches table now that players exists
        Schema::table('matches', function (Blueprint $table) {
            $table->foreign('winner_id')->references('id')->on('players');
        });
    }
};
```

**Purpose**: Game participants pivot table  
**Key Features**:
- Direct FK to users table (simplified from polymorphic)
- `position_id`: Turn order in match
- `name`: Player display name (may differ from user name)
- `color`: Visual identifier in game UI
- Unique constraint ensures no duplicate positions per match
- **Important**: Adds winner_id FK back to matches table

---

### Migration 009: `create_moves_table`

**File**: `database/migrations/2025_11_13_000009_create_moves_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('matches');
            $table->foreignId('player_id')->constrained('players');
            $table->integer('turn_number');
            $table->json('move_details');
            $table->timestamps();
        });
    }
};
```

**Purpose**: Complete move history for every match  
**Key Features**:
- `move_details`: JSON column for flexible move data (e.g., `{"column": 3}`, `{"card_id": 42}`)
- Enables replay functionality
- Audit trail for game integrity

---

## Phase 3: Billing & Quota System

### Migration 010: `create_strikes_table`

**File**: `database/migrations/2025_11_13_000010_create_strikes_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('title_slug', 50);
            $table->tinyInteger('strikes_used')->default(0);
            $table->date('strike_date');
            
            $table->unique(['user_id', 'title_slug', 'strike_date']);
            $table->timestamps();
        });
    }
};
```

**Purpose**: Free tier "3 strikes" daily loss tracking  
**Key Features**:
- Tracks losses per game per day (EST timezone)
- Unique constraint prevents duplicate records
- `strikes_used`: Count of losses (0-3)
- Resets daily at midnight EST

---

### Migration 011: `create_quotas_table`

**File**: `database/migrations/2025_11_13_000011_create_quotas_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('title_slug', 50);
            $table->integer('matches_started')->default(0);
            $table->date('reset_month');
            
            $table->unique(['user_id', 'title_slug', 'reset_month']);
            $table->timestamps();
        });
    }
};
```

**Purpose**: Member tier monthly match quota tracking  
**Key Features**:
- Tracks matches started per game per month (EST timezone)
- `reset_month`: First day of month (YYYY-MM-01)
- Unique constraint prevents duplicate records
- Default limit: 2,000 matches per game per month

---

## Phase 4: Gamification System

### Migration 012: `create_point_ledgers_table`

**File**: `database/migrations/2025_11_13_000012_create_point_ledgers_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            
            // Polymorphic relation to source (e.g., Match, Badge)
            $table->morphs('source');
            
            $table->integer('points')->comment('Positive (award) or negative (deduction)');
            $table->string('description', 100);
            $table->timestamps();
        });
    }
};
```

**Purpose**: Immutable audit trail for all point transactions  
**Key Features**:
- Polymorphic `source` links to matches, badges, etc.
- Supports both positive and negative point adjustments
- `description`: Human-readable transaction reason

---

### Migration 013: `create_global_ranks_table`

**File**: `database/migrations/2025_11_13_000013_create_global_ranks_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_ranks', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users');
            $table->integer('total_points')->default(0)->index();
            $table->integer('rank')->nullable();
            $table->timestamps();
        });
    }
};
```

**Purpose**: Cached ranking data for fast leaderboard queries  
**Key Features**:
- `user_id` as primary key (one record per user)
- `total_points`: Aggregated from point_ledgers via scheduled task
- `rank`: Calculated numerical rank (1, 2, 3...)
- Index on total_points for efficient sorting

---

### Migration 014: `create_badges_table`

**File**: `database/migrations/2025_11_13_000014_create_badges_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->string('image_url');
            $table->json('condition_json')->comment('Unlock criteria, e.g., {"wins": 10}');
            $table->timestamps();
        });
    }
};
```

**Purpose**: Static badge definitions  
**Key Features**:
- `slug`: Unique identifier (e.g., 'first-win', 'streak-master')
- `condition_json`: Flexible unlock criteria
- One-time achievements

---

### Migration 015: `create_user_badge_table`

**File**: `database/migrations/2025_11_13_000015_create_user_badge_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_badge', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('badge_id')->constrained('badges');
            $table->timestamp('earned_at')->useCurrent();
            
            $table->primary(['user_id', 'badge_id']);
        });
    }
};
```

**Purpose**: User badge ownership pivot table  
**Key Features**:
- Composite primary key prevents duplicate badges
- `earned_at`: Timestamp for achievement tracking

---

### Migration 016: `create_user_title_levels_table`

**File**: `database/migrations/2025_11_13_000016_create_user_title_levels_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_title_levels', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->string('title_slug', 50);
            
            $table->tinyInteger('level')->default(1);
            $table->integer('xp_current')->default(0)->comment('XP toward next level');
            $table->timestamp('last_played_at')->useCurrent();
            
            $table->primary(['user_id', 'title_slug']);
            $table->timestamps();
        });
    }
};
```

**Purpose**: Game-specific skill progression and decay tracking  
**Key Features**:
- Composite primary key: one record per user per game
- `level`: Current skill level (1+)
- `xp_current`: Progress toward next level
- `last_played_at`: **CRITICAL** for decay calculations

---

### Migration 017: `create_user_daily_point_summaries_table`

**File**: `database/migrations/2025_11_13_000017_create_user_daily_point_summaries_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_daily_point_summaries', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->date('date')->index();
            $table->integer('points_earned')->default(0);
            
            $table->primary(['user_id', 'date']);
            $table->timestamps();
        });
    }
};
```

**Purpose**: Historical daily leaderboard data  
**Key Features**:
- Enables "Leaderboard for Date X" queries
- Populated by scheduled task at end of day
- Composite primary key prevents duplicates

---

### Migration 018: `create_user_monthly_point_summaries_table`

**File**: `database/migrations/2025_11_13_000018_create_user_monthly_point_summaries_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_monthly_point_summaries', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->string('month', 7);
            $table->integer('points_earned')->default(0);
            
            $table->primary(['user_id', 'month']);
            $table->timestamps();
        });
    }
};
```

**Purpose**: Historical monthly leaderboard data  
**Key Features**:
- `month`: Format YYYY-MM (e.g., '2025-11')
- Enables "Top Players of Month X" queries
- Populated by scheduled task at end of month

---

## Eloquent Models

### Model Directory Structure

```
app/Models/
├── Auth/
│   ├── User.php
│   ├── Agent.php
│   └── Entry.php
├── Access/
│   └── Client.php
├── Content/
│   └── Avatar.php
├── Title/
│   └── Title.php
├── Game/
│   ├── Game.php
│   ├── Player.php
│   └── Move.php
├── Billing/
│   ├── Strike.php
│   └── Quota.php
└── Gamification/
    ├── PointLedger.php
    ├── GlobalRank.php
    ├── Badge.php
    └── UserTitleLevel.php
```

---

### Model 001: `App\Models\Auth\User`

**File**: `app/Models/Auth/User.php`

```php
<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;
use App\Models\Content\Avatar;
use App\Models\Match\Player;
use App\Models\Auth\Agent;
use App\Models\Auth\Session;
use App\Models\Billing\Strike;
use App\Models\Billing\Quota;
use App\Models\Gamification\PointLedger;
use App\Models\Gamification\GlobalRank;
use App\Models\Gamification\Badge;
use App\Models\Gamification\UserTitleLevel;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'agent_id',
        'avatar_id',
        'deactivated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    // Relationships
    public function avatar()
    {
        return $this->belongsTo(Avatar::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function strikes()
    {
        return $this->hasMany(Strike::class);
    }

    public function quotas()
    {
        return $this->hasMany(Quota::class);
    }

    public function pointLedgers()
    {
        return $this->hasMany(PointLedger::class);
    }

    public function globalRank()
    {
        return $this->hasOne(GlobalRank::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badge')
            ->withPivot('earned_at');
    }

    public function gameLevels()
    {
        return $this->hasMany(UserTitleLevel::class);
    }

    // Helper methods
    public function isAgent(): bool
    {
        return $this->agent_id !== null;
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }
}
```

---

### Model 002: `App\Models\Auth\Agent`

**File**: `app/Models/Auth/Agent.php`

```php
<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_logic_path',
        'available_hour_est',
    ];

    protected $casts = [
        'available_hour_est' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->hasOne(User::class);
    }
}
```

---

### Model 003: `App\Models\Content\Avatar`

**File**: `app/Models/Content/Avatar.php`

```php
<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
use DrewRoberts\Media\Models\Image;

class Avatar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_id',
        'type',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    // Relationships
    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
```

---

### Model 004: `App\Models\Access\Client`

**File**: `app/Models/Access/Client.php`

```php
<?php

namespace App\Models\Access;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\Session;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_key',
        'platform',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }
}
```

---

### Model 005: `App\Models\Auth\Entry`

**File**: `app/Models/Auth/Entry.php`

**Purpose**: Tracks user entries (login sessions) to the GamerProtocol platform through client frontends.

```php
<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Access\Client;

class Entry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'ip_address',
        'device_info',
        'token_id',
        'logged_in_at',
        'logged_out_at',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
```

---

### Model 006: `App\Models\Game\Game`

**File**: `app/Models/Game/Title.php`

```php
<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Match\Match;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'max_players',
    ];

    protected $casts = [
        'max_players' => 'integer',
    ];

    // Relationships
    public function matches()
    {
        return $this->hasMany(Match::class, 'title_slug', 'slug');
    }
}
```

---

### Model 007: `App\Models\Match\Match`

**File**: `app/Models/Match/Game.php`

```php
<?php

namespace App\Models\Match;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Match\Player;
use App\Models\Match\Move;

class Match extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
        'title_slug',
        'status',
        'created_by_user_id',
        'winner_id',
        'turn_number',
        'game_state',
    ];

    protected $casts = [
        'game_state' => 'array',
        'turn_number' => 'integer',
    ];

    // Use ULID for route model binding
    public function getRouteKeyName()
    {
        return 'ulid';
    }

    // Relationships
    public function game()
    {
        return $this->belongsTo(Game::class, 'title_slug', 'slug');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function winner()
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }

    // Helper methods
    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
```

---

### Model 008: `App\Models\Match\Player`

**File**: `app/Models/Match/Player.php`

```php
<?php

namespace App\Models\Match;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
use App\Models\Match\Match;
use App\Models\Match\Move;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'user_id',
        'name',
        'position_id',
        'color',
    ];

    protected $casts = [
        'position_id' => 'integer',
    ];

    // Relationships
    public function match()
    {
        return $this->belongsTo(Match::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }
}
```

---

### Model 009: `App\Models\Match\Move`

**File**: `app/Models/Match/Move.php`

```php
<?php

namespace App\Models\Match;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Move extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_id',
        'turn_number',
        'move_details',
    ];

    protected $casts = [
        'move_details' => 'array',
        'turn_number' => 'integer',
    ];

    // Relationships
    public function match()
    {
        return $this->belongsTo(Match::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
```

---

### Model 010: `App\Models\Billing\Strike`

**File**: `app/Models/Billing/Strike.php`

```php
<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class Strike extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title_slug',
        'strikes_used',
        'strike_date',
    ];

    protected $casts = [
        'strike_date' => 'date',
        'strikes_used' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

### Model 011: `App\Models\Billing\Quota`

**File**: `app/Models/Billing/Quota.php`

```php
<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class Quota extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title_slug',
        'matches_started',
        'reset_month',
    ];

    protected $casts = [
        'reset_month' => 'date',
        'matches_started' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

### Model 012: `App\Models\Gamification\PointLedger`

**File**: `app/Models/Gamification/PointLedger.php`

```php
<?php

namespace App\Models\Gamification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class PointLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source_type',
        'source_id',
        'points',
        'description',
    ];

    protected $casts = [
        'points' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function source()
    {
        return $this->morphTo();
    }
}
```

---

### Model 013: `App\Models\Gamification\GlobalRank`

**File**: `app/Models/Gamification/GlobalRank.php`

```php
<?php

namespace App\Models\Gamification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class GlobalRank extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'total_points',
        'rank',
    ];

    protected $casts = [
        'total_points' => 'integer',
        'rank' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

### Model 014: `App\Models\Gamification\Badge`

**File**: `app/Models/Gamification/Badge.php`

```php
<?php

namespace App\Models\Gamification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'image_url',
        'condition_json',
    ];

    protected $casts = [
        'condition_json' => 'array',
    ];

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badge')
            ->withPivot('earned_at');
    }
}
```

---

### Model 015: `App\Models\Gamification\UserTitleLevel`

**File**: `app/Models/Gamification/UserTitleLevel.php`

```php
<?php

namespace App\Models\Gamification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class UserTitleLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title_slug',
        'level',
        'xp_current',
        'last_played_at',
    ];

    protected $casts = [
        'level' => 'integer',
        'xp_current' => 'integer',
        'last_played_at' => 'datetime',
    ];

    // Composite primary key
    protected $primaryKey = ['user_id', 'title_slug'];
    public $incrementing = false;

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## Summary of Key Design Decisions

### 1. **No Down Methods**
All migrations omit the `down()` method as requested, focusing on forward-only schema changes.

### 2. **ULID for Public IDs**
The `games` table uses ULIDs instead of auto-increment IDs for public-facing routes, providing better security and preventing enumeration attacks.

### 3. **JSON Columns for Flexibility**
- `matches.game_state`: Stores board/hand state without schema changes
- `moves.move_details`: Flexible move data per game type
- `badges.condition_json`: Dynamic unlock criteria

### 4. **Simplified Player Model**
Removed polymorphic relationship in favor of direct `user_id` foreign key for better performance and simplicity.

### 5. **Composite Primary Keys**
Used for naturally unique combinations:
- `user_title_levels`: (user_id, title_slug)
- `user_badge`: (user_id, badge_id)
- `strikes`: (user_id, title_slug, strike_date)
- `quotas`: (user_id, title_slug, reset_month)

### 6. **Cascading Foreign Keys**
All foreign keys use Laravel's default behavior (no explicit cascade). Application logic handles relationship integrity.

### 7. **Indexed Columns**
Strategic indexes on:
- `matches.ulid`: Route lookups
- `matches.title_slug`: Game filtering
- `users.deactivated_at`: Active user queries
- `global_ranks.total_points`: Leaderboard sorting
- `user_daily_point_summaries.date`: Historical queries

---

## Implementation Checklist

- [ ] Run all 18 migrations in order
- [ ] Create all 15 model files with relationships
- [ ] Verify foreign key constraints
- [ ] Seed initial data:
  - [ ] Games table (validate-four, checkers, hearts, spades)
  - [ ] Avatars table (free tier avatars)
  - [ ] Clients table (web, ios, android clients)
  - [ ] Badges table (initial achievement definitions)
- [ ] Test model relationships in tinker
- [ ] Set up scheduled tasks for:
  - [ ] Daily strike resets (midnight EST)
  - [ ] Monthly quota resets (1st of month EST)
  - [ ] Level decay checks (daily)
  - [ ] Global rank updates (hourly)
  - [ ] Daily/monthly point summary aggregation

---

**Total Tables**: 18  
**Total Models**: 15  
**Dependencies Resolved**: ✓  
**Ready for Implementation**: ✓
