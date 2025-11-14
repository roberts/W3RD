<?php

namespace App\Models\Auth;

use App\Models\Billing\Quota;
use App\Models\Billing\Strike;
use App\Models\Content\Avatar;
use App\Models\Game\Player;
use App\Models\Gamification\Badge;
use App\Models\Gamification\GlobalRank;
use App\Models\Gamification\PointLedger;
use App\Models\Gamification\UserTitleLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Billable, HasApiTokens, HasFactory, Notifiable;

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

    public function titleLevels()
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
