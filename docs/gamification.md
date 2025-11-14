## Final Gamification Model Specification

### I. Feature Scope and Metrics

| Feature | Scope | Metric | Purpose |
| :--- | :--- | :--- | :--- |
| **Points** | **System-Wide** | Total points earned (via matches, bonuses). | Determines **Global Rank** (`/v1/leaderboard`). |
| **Levels** | **Game-Specific** | Experience Points (XP) earned toward mastery in one game (e.g., Hearts Level 5). | Determines skill progression and decay status. |
| **Badges** | **System-Wide** | Achieved once per user for meeting a site-wide milestone. | Permanent prestige asset. |
| **Decay** | **Game-Specific** | Reduces **Level** if a user is inactive in a specific game. | Drives retention and continued engagement. |

---

### II. Database Models (Recap)

| Model Name | Purpose | Key Columns / Fields |
| :--- | :--- | :--- |
| `App\Models\Gamification\PointLedger` | Records all point transactions (for auditing). | `user_id`, `source_id`, `source_type` (Polymorphic), `points`, `description`. |
| `App\Models\Gamification\GlobalRank` | Stores aggregated total points for fast lookup. | `user_id`, `total_points`, `rank`. |
| `App\Models\Gamification\Badge` | Static definition of all badges available. | `slug`, `name`, `condition_json` (Defines unlock criteria). |
| `user_badge` | Pivot table linking users to earned badges. | `user_id`, `badge_id`. |
| **`App\Models\Gamification\UserGameLevel`** | **CRITICAL:** Tracks skill progress per game. | `user_id`, **`game_slug`** (Composite Key), **`level`**, **`xp_current`**, **`last_played_at`** (Required for Decay). |

---

### III. Logic Implementation and Service Triggers

All logic resides in the `GamificationService` and runs via events or scheduled tasks.

#### A. Match Completion Logic (Instant Triggers)

The `GamificationService->processMatchResults(Match $match)` is called immediately after a game finishes.

| Action | Logic | Database Write |
| :--- | :--- | :--- |
| **Award Points** | Calculates points based on win/loss/difficulty multipliers. | Writes transaction to **`PointLedger`**. |
| **Award XP/Level Up** | Calculates XP specific to the `game_slug`. Increments `level` and resets `xp_current` in **`UserGameLevel`**. Updates **`last_played_at`**. | Writes/updates **`UserGameLevel`**. |
| **Check Badges** | Checks user's current stats against all unearned badges defined in the `Badge` table's `condition_json`. | Writes to **`user_badge`** pivot table. |

#### B. Level/Rank Decay Logic (Scheduled Task)

The decay process must be implemented as a dedicated scheduled task to run outside of the user request cycle.

| Task | Schedule | Logic |
| :--- | :--- | :--- |
| **Decay Process** | Hourly or Daily (EST) | 1. **Query:** Select all rows from **`UserGameLevel`** where the difference between the current time and **`last_played_at`** is greater than the inactivity threshold (e.g., 7 days). 2. **Execute Decay:** For each stale record, reduce the **`level`** by a defined amount (e.g., 1 level). 3. **Minimal Level:** Ensure the level cannot drop below a specified minimum (e.g., Level 1). |
| **Ranking Update** | Hourly (EST) | Recalculates all **`GlobalRank`** records by summing the `PointLedger`. |

---

### IV. API Endpoint Exposure (Recap)

| Endpoint | Purpose | Logic Source |
| :--- | :--- | :--- |
| `GET /v1/user/stats` | User's profile, including **total points** and all **earned badges**. | `GlobalRank`, `user_badge`. |
| `GET /v1/user/levels` | Lists the user's current **Level** and **XP** for *every* game they have played. | **`UserGameLevel`**. |
| `GET /v1/leaderboard` | Global ranking based on **Total Points**. | **`GlobalRank`**. |
| `GET /v1/games/{slug}/leaderboard` | Game-specific ranking (e.g., Win Percentage or Total Wins). | `MatchController` delegates to the respective **Game Service Handler** for query execution. |



## 💾 Gamification Feature Migrations

### 1\. `create_point_ledgers_table` (Transactional History)

This table tracks every point transaction, serving as the immutable source of truth for all points earned and spent.

```php
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
            
            // Polymorphic relation to the source (e.g., a Match)
            $table->morphs('source'); 
            
            $table->integer('points')->comment('Can be positive (award) or negative (deduction/decay).');
            $table->string('description', 100); 
            $table->timestamps();
        });
    }
};
```

### 2\. `create_global_ranks_table` (Fast Ranking Lookup)

This table stores the calculated, aggregated rank data, updated by a scheduled task, to avoid slow joins on every leaderboard request.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_ranks', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users'); // User ID as PK
            $table->integer('total_points')->default(0)->index(); 
            $table->integer('rank')->nullable(); // Actual numerical rank (1, 2, 3...)
            $table->timestamps();
        });
    }
};
```

### 3\. `create_badges_table` (Badge Definitions)

This defines the static list of all available badges and their unlock criteria.

```php
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
            $table->json('condition_json')->comment('Defines requirements, e.g., {"game_slug": "validate-four", "wins": 10}');
            $table->timestamps();
        });
    }
};
```

### 4\. `create_user_badge_table` (Badge Pivot)

The pivot table to link users to the badges they have earned.

```php
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

### 5\. `create_user_game_levels_table` (Game-Specific Levels & Decay)

This is the table for tracking skill progression and applying the decay logic.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_levels', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->string('game_slug', 50); // The game being leveled up
            
            $table->tinyInteger('level')->default(1);
            $table->integer('xp_current')->default(0)->comment('XP toward the next level');
            $table->timestamp('last_played_at')->useCurrent(); // CRITICAL for Decay logic
            
            $table->primary(['user_id', 'game_slug']);
            $table->timestamps();
        });
    }
};
```

## 📊 Historical and Agent Management Tables

### 6\. `create_user_daily_point_summaries_table` (Daily Leaderboards)

Stores final points earned per day for historical daily leaderboards.

```php
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

### 7\. `create_user_monthly_point_summaries_table` (Monthly Leaderboards)

Stores final points earned per month.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_monthly_point_summaries', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->string('month', 7); // Format: YYYY-MM
            $table->integer('points_earned')->default(0);

            $table->primary(['user_id', 'month']);
            $table->timestamps();
        });
    }
};
```

### 8\. `update_users_table_for_agent_flag` (Agent User Flag)

Adds the flag to the core `users` table to distinguish human accounts from agent profiles.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Flag to identify user records that are actually controlled by the AI/Bot system
            $table->boolean('is_agent')->default(false)->after('password');
            // This column is redundant if using the dedicated Agent model, but useful for quick queries on the users table.
        });
    }
};
```

### 9\. `create_agent_configs_table` (Agent User Configuration)

This table stores the configuration for the specialized "Agent Users."

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_configs', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users'); 
            $table->string('strategy_class'); // The FQCN of the AI strategy handler
            $table->tinyInteger('available_hour_est')->comment('The single hour (0-23) the agent is active.');
            $table->timestamps();
        });
    }
};
```