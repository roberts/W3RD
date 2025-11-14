You're looking for the final PHP structure of your Laravel models for the **GamerProtocol.io** API, which adheres to the domain structure and special features (ULIDs, JSON casting, polymorphism) we designed.

Here are the detailed model definitions, organized by domain, ready for implementation in your `app/Models/` subdirectories.

-----

## 💻 Core Identity & Access Models

These models handle all entities related to users, agents, and access clients under a unified architecture.

### 1. `App\Models\Auth\User.php`

The central model for any player, whether human or AI. It includes traits for authentication and billing, and now links to an optional Agent profile.

```php
<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;
use App\Models\Content\Avatar;
use App\Models\Game\Player;
use App\Models\Auth\Agent;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable;

    protected $fillable = [
        'name',         // Display Name
        'username',     // Unique login/handle
        'email',
        'password',
        'agent_id',     // Link to the agent profile
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

    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    // Helper method to easily check if the user is a bot
    public function isAgent(): bool
    {
        return $this->agent_id !== null;
    }
}
```

### 2. `App\Models\Auth\Agent.php`

This model is now a simple "profile" that extends a User, holding only AI-specific data.

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

    // An Agent profile belongs to exactly one User
    public function user()
    {
        return $this->hasOne(User::class);
    }
}
```

### 3. `App\Models\Content\Avatar.php`
```php
<?php
// ... (content largely unchanged, but the agents() relationship can be removed)
```

### 4. `App\Models\Access\Client.php`
```php
<?php
// ... (content unchanged)
```

### 5. `App\Models\Auth\Entry.php`

**Purpose:** Tracks each entry (login session) when a user accesses the GamerProtocol platform through any client frontend.

```php
<?php
// ... (content unchanged)
```

-----

## ♟️ Game Domain Models

### 6. `App\Enums\GameTitle.php`

Game titles are defined as PHP enums rather than database records, providing compile-time safety and eliminating database queries.

```php
<?php

namespace App\Enums;

enum GameTitle: string
{
    case VALIDATE_FOUR = 'validate-four';
    case CHECKERS = 'checkers';
    case HEARTS = 'hearts';
    case SPADES = 'spades';

    public function label(): string
    {
        return match ($this) {
            self::VALIDATE_FOUR => 'Validate Four',
            self::CHECKERS => 'Checkers',
            self::HEARTS => 'Hearts',
            self::SPADES => 'Spades',
        };
    }

    public function maxPlayers(): int
    {
        return match ($this) {
            self::VALIDATE_FOUR => 2,
            self::CHECKERS => 2,
            self::HEARTS => 4,
            self::SPADES => 4,
        };
    }

    public function slug(): string
    {
        return $this->value;
    }
}
```

### 6b. `App\Enums\ActionType.php`

Action types are defined as PHP enums to provide type safety for game actions across different titles.

```php
<?php

namespace App\Enums;

enum ActionType: string
{
    case DROP_PIECE = 'drop_piece';
    case MOVE_PIECE = 'move_piece';
    case PLAY_CARD = 'play_card';
    case PASS = 'pass';
    case DRAW_CARD = 'draw_card';
    case BID = 'bid';

    public function label(): string
    {
        return match($this) {
            self::DROP_PIECE => 'Drop Piece',
            self::MOVE_PIECE => 'Move Piece',
            self::PLAY_CARD => 'Play Card',
            self::PASS => 'Pass',
            self::DRAW_CARD => 'Draw Card',
            self::BID => 'Bid',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::DROP_PIECE => 'Place a piece on the board (e.g., Connect Four)',
            self::MOVE_PIECE => 'Move a piece on the board (e.g., Checkers)',
            self::PLAY_CARD => 'Play a card from hand (e.g., Hearts, Spades)',
            self::PASS => 'Skip turn or pass',
            self::DRAW_CARD => 'Draw a card from deck',
            self::BID => 'Place a bid (e.g., Spades bidding)',
        };
    }
}
```

### 7. `App\Models\Game\Game.php`

The Game model uses enum casting for the title_slug field, automatically converting between the database string and the GameTitle enum.

```php
<?php

namespace App\Models\Game;

use App\Enums\GameTitle;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
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
        'title_slug' => GameTitle::class,
        'game_state' => 'array',
        'turn_number' => 'integer',
    ];

    public function getRouteKeyName()
    {
        return 'ulid';
    }

    // Relationships
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

    public function actions()
    {
        return $this->hasMany(Action::class);
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

### 8. `App\Models\Game\Player.php`

The `Player` model is now greatly simplified, removing the polymorphic relationship.

```php
<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'user_id', // Direct FK to the users table
        'name',
        'position_id',
        'color',
    ];

    // Relationships
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    // A player is now directly associated with a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }
}
```

### 9. `App\Models\Game\Action.php`

```php
<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ActionType;

class Action extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_id',
        'turn_number',
        'action_type',
        'action_details',
        'status',
        'error_code',
        'timestamp_client',
    ];

    // Cast the JSON column and ActionType enum
    protected $casts = [
        'action_type' => ActionType::class,
        'action_details' => 'array',
        'turn_number' => 'integer',
        'timestamp_client' => 'datetime',
    ];

    // Relationships
    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
```

-----

## 💰 Billing & Quota Models

These models enforce your unique tiered subscription rules.

### 10\. `App\Models\Billing\Strike.php`

```php
<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class Strike extends Model
{
    use HasFactory;

    // Table name is 'strikes'
    protected $table = 'strikes';

    protected $fillable = [
        'user_id',
        'title_slug',
        'strikes_used',
        'strike_date',
    ];

    protected $casts = [
        'strike_date' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 11\. `App\Models\Billing\Quota.php`

```php
<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class Quota extends Model
{
    use HasFactory;

    // Table name is 'quotas'
    protected $table = 'quotas';

    protected $fillable = [
        'user_id',
        'title_slug',
        'games_started',
        'reset_month',
    ];

    protected $casts = [
        'reset_month' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```