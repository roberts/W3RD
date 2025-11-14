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

### 6. `App\Models\Game\Title.php`

The Title model defines available game titles (like chess, checkers, hearts).

```php
<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Title extends Model
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
    public function games()
    {
        return $this->hasMany(Game::class, 'title_slug', 'slug');
    }
}
```

### 7. `App\Models\Game\Game.php`
```php
<?php
// ... (content unchanged)
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

    public function moves()
    {
        return $this->hasMany(Move::class);
    }
}
```

### 9\. `App\Models\Game\Move.php`

```php
<?php

namespace App\Models\Game;

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

    // Cast the JSON column
    protected $casts = [
        'move_details' => 'array',
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