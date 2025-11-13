# 🏛️ GamerProtocol.io API Database Schema (Migrations)

This document outlines the final database migration structure for the **GamerProtocol.io** API. The schema is designed for **scalability**, **data integrity** (no deletions/cascades), and **flexible multi-game support** using JSON casting and polymorphic relationships.

---

## 💻 Core Identity & Access

These migrations establish the foundation for users, agents, and tracking access via different frontends.

### 1. `create_avatars_table` (Reusable Identity Assets)

```php
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
            $table->string('image_url');
            $table->enum('type', ['free', 'premium', 'nft'])->default('free');
            $table->timestamps();
        });
    }
};
````

### 2\. `create_agents_table` (AI and Local Player Identities)

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->enum('agent_type', ['ai', 'local_human', 'anonymous']);
            $table->foreignId('avatar_id')->nullable()->constrained('avatars');
            $table->string('ai_logic_path')->nullable();
            $table->timestamps();
        });
    }
};
```

### 3\. `create_interfaces_table` (Frontend Application Keys)

This table defines each unique frontend application accessing the API, allowing for centralized key management and usage tracking.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interfaces', function (Blueprint $table) {
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

### 4\. `update_users_table` (Account Status and Avatar Link)

Modifies the default `users` table to include **subscription** and **deactivation** flags.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('avatar_id')->nullable()->constrained('avatars');
            // Laravel Cashier fields
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            
            // Deactivation Field
            $table->timestamp('deactivated_at')->nullable()->index(); 
        });
    }
};
```

### 5\. `create_sessions_table` (User Login Log)

Tracks user login events for security and auditing, linking the session to the specific frontend interface used.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('interface_id')->constrained('interfaces'); 
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info', 512)->nullable(); 
            $table->string('token_id', 100)->nullable(); 
            $table->timestamp('logged_in_at')->useCurrent();
            $table->timestamp('logged_out_at')->nullable();
        });
    }
};
```

-----

## ♟️ Match, Player, and History Structure

This is the unified core for all games, featuring the public **ULID** and flexible **JSON** state.

### 6\. `create_games_table` (Game Blueprints)

```php
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

### 7\. `create_matches_table` (Game Instances)

The central table, using a **ULID** for public API referencing.

```php
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
            
            $table->string('game_slug', 50)->index(); 
            $table->enum('status', ['pending', 'active', 'finished'])->default('pending');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users');
            
            // winner_id FK set in the next migration after 'players' exists
            $table->unsignedBigInteger('winner_id')->nullable(); 
            
            $table->integer('turn_number')->default(0);
            $table->json('game_state'); // Flexible state storage for any game
            $table->timestamps();
        });
    }
};
```

### 8\. `create_players_table` (Match Participants)

The **polymorphic** table linking `matches` to either `users` or `agents`.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches'); 
            $table->morphs('playable'); 
            $table->string('name', 50);
            $table->tinyInteger('position_id')->comment('Turn order, e.g., 1, 2, 3, 4');
            $table->string('color', 20); 
            
            $table->unique(['match_id', 'position_id']);
            $table->timestamps();
        });
        
        // Finalizing the FK after the 'players' table exists
        Schema::table('matches', function (Blueprint $table) {
            $table->foreign('winner_id')->references('id')->on('players');
        });
    }
};
```

### 9\. `create_moves_table` (Match History Log)

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches');
            $table->foreignId('player_id')->constrained('players');
            $table->integer('turn_number');
            $table->json('move_details');
            $table->timestamps();
        });
    }
};
```

-----

## 💰 Billing and Quota Logic

These tables handle the usage limits for the free and member subscription tiers.

### 10\. `create_strikes_table` (Free Tier Logic)

Tracks the "3 strikes and out" limit per game per day (EST).

```php
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
            $table->string('game_slug', 50);
            $table->tinyInteger('strikes_used')->default(0);
            $table->date('strike_date');
            
            $table->unique(['user_id', 'game_slug', 'strike_date']);
            $table->timestamps();
        });
    }
};
```

### 11\. `create_quotas_table` (Member Tier Logic)

Tracks the "2,000 matches per month" quota per game (EST calendar month).

```php
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
            $table->string('game_slug', 50);
            $table->integer('matches_started')->default(0);
            $table->date('reset_month'); 
            
            $table->unique(['user_id', 'game_slug', 'reset_month']);
            $table->timestamps();
        });
    }
};
```
