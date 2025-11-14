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
use App\Models\Gamification\UserGameLevel;

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

    public function sessions()
    {
        return $this->hasMany(Session::class);
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
        return $this->hasMany(UserGameLevel::class);
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
