Additional Migrations & Models.

### 2\. `create_registrations_table` (Delayed User Creation/Rev Share Source)

This table handles sign-up data and revenue attribution *before* a user verifies their email and is created in the `users` table.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients');
            $table->string('email')->unique();
            $table->string('password'); 
            $table->string('verification_token')->unique();
            
            // This links to the final 'users' record upon successful verification.
            $table->foreignId('user_id')->nullable()->constrained('users');
            
            $table->timestamps();
        });
    }
};
```

### 4\. `create_modes_table` (Game Variations)

Defines variations like "Blitz" or "No-Queen-Break."

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('title_id')->constrained('titles'); 
            $table->string('slug', 50);
            $table->string('name');
            $table->json('rules_override_json')->nullable()->comment('Specific rule changes for this mode');

            $table->unique(['title_id', 'slug']);
            $table->timestamps();
        });
    }
};
```



All of the migrations & models below should be added to the expansion.md file.

### 6\. `create_clans_table` (Social Structure)

Defines persistent groups for collaboration and competition.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('leader_id')->constrained('users');
            $table->json('resource_pool_json')->nullable()->comment('Shared resources/bank');
            $table->timestamps();
        });
    }
};
```

### 7\. `create_clan_members_table` (Clan Pivot)

Links users to their clan and defines their role within the social structure.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clan_members', function (Blueprint $table) {
            $table->foreignId('clan_id')->constrained('clans');
            $table->foreignId('user_id')->constrained('users');
            $table->string('rank_title', 50)->default('Member');
            $table->primary(['clan_id', 'user_id']);
            $table->timestamps();
        });
    }
};
```

### 8\. `create_user_resources_table` (Persistent Inventory)

Tracks global resources for complex strategy games, separated by game type.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_resources', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->string('game_slug', 50); // The game this resource is tied to
            $table->string('resource_type', 50); // e.g., 'gold', 'wood', 'population'
            $table->decimal('quantity', 14, 4)->default(0);

            $table->primary(['user_id', 'game_slug', 'resource_type'], 'user_resource_pk');
            $table->timestamps();
        });
    }
};
```

### 9\. `create_skill_ratings_table` (ELO/MMR Tracking)

Tracks advanced matchmaking rating separate from experience level.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_ratings', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->string('game_slug', 50); 
            $table->integer('rating_value')->default(1500)->comment('Standard ELO or MMR rating');
            $table->integer('games_played')->default(0);

            $table->primary(['user_id', 'game_slug']);
            $table->timestamps();
        });
    }
};
```