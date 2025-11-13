You've chosen a key retention feature. Implementing **Level/Rank Decay** requires integrating time-based logic into your existing gamification structure.

Here is the full, consolidated recap of the Gamification features, including the new Decay logic, structured for easy implementation by your coding agent.

---

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