You're looking for the final PHP structure of your Laravel models for the **GamerProtocol.io** API, which adheres to the domain structure and special features (ULIDs, JSON casting, polymorphism) we designed.

Here are the detailed model definitions, organized by domain, ready for implementation in your `app/Models/` subdirectories.

-----

## 💻 Core Identity & Access Models

These models handle all entities related to users, agents, and access interfaces.

### 1\. `App\Models\Auth\User.php`

Includes the `HasApiTokens` trait (for Sanctum), `Billable` trait (for Cashier), and the custom `HasUuids` for the primary key (assuming you're using default Laravel primary key, which should be **BIGINT** for most databases, but we'll include the necessary foreign key relationships).

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

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable;

    // The fields you'll allow mass assignment on
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_id',
        'deactivated_at',
    ];

    // Hidden fields for arrays
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Casts for data integrity
    protected $casts = [
        'email_verified_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    // Relationships
    public function avatar()
    {
        return $this->belongsTo(Avatar::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    // Polymorphic relationship to Match Players
    public function players()
    {
        return $this->morphMany(Player::class, 'playable');
    }
}
```

### 2\. `App\Models\Auth\Agent.php`

```php
<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Content\Avatar;
use App\Models\Match\Player;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'agent_type',
        'avatar_id',
        'ai_logic_path',
    ];

    // Relationships
    public function avatar()
    {
        return $this->belongsTo(Avatar::class);
    }

    // Polymorphic relationship to Match Players
    public function players()
    {
        return $this->morphMany(Player::class, 'playable');
    }
}
```

### 3\. `App\Models\Content\Avatar.php`

```php
<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
use App\Models\Auth\Agent;

class Avatar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_url',
        'type',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function agents()
    {
        return $this->hasMany(Agent::class);
    }
}
```

### 4\. `App\Models\Access\Interface.php`

```php
<?php

namespace App\Models\Access;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\Session;

class Interface extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_key',
        'platform',
        'is_active',
    ];

    // Relationships
    public function sessions()
    {
        return $this->hasMany(Session::class);
    }
}
```

### 5\. `App\Models\Auth\Session.php`

```php
<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Access\Interface as FrontendInterface; // Use an alias to avoid naming conflict

class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'interface_id',
        'ip_address',
        'device_info',
        'token_id',
        'logged_out_at',
    ];

    // Casts
    protected $casts = [
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function frontendInterface()
    {
        return $this->belongsTo(FrontendInterface::class, 'interface_id');
    }
}
```

-----

## ♟️ Match & Game Domain Models

These models define the core gameplay structure, utilizing **ULIDs** and **JSON casting**.

### 6\. `App\Models\Game\Game.php`

```php
<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'max_players',
    ];
}
```

### 7\. `App\Models\Match\Match.php`

This is the core model. It uses the `Ulid` trait (which you'll need to install or implement) and casts the crucial `game_state` column.

```php
<?php

namespace App\Models\Match;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
use Illuminate\Support\Str;

class Match extends Model
{
    use HasFactory;

    // Use a custom booting method to automatically generate the ULID
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    protected $fillable = [
        'ulid',
        'game_slug',
        'status',
        'created_by_user_id',
        'winner_id',
        'turn_number',
        'game_state',
    ];

    // Cast the JSON column to a PHP array/object
    protected $casts = [
        'game_state' => 'array',
    ];

    // Relationships
    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function winner()
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }
}
```

### 8\. `App\Models\Match\Player.php`

The **polymorphic** player model linking participants to their match.

```php
<?php

namespace App\Models\Match;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'playable_id',
        'playable_type',
        'name',
        'position_id',
        'color',
    ];

    // Relationships
    public function match()
    {
        return $this->belongsTo(Match::class);
    }

    // Polymorphic relationship - The player's identity (User or Agent)
    public function playable()
    {
        return $this->morphTo();
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }
}
```

### 9\. `App\Models\Match\Move.php`

```php
<?php

namespace App\Models\Match;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Move extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'player_id',
        'turn_number',
        'move_details',
    ];

    // Cast the JSON column
    protected $casts = [
        'move_details' => 'array',
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
        'game_slug',
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
        'game_slug',
        'matches_started',
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