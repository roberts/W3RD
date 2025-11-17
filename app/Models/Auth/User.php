<?php

namespace App\Models\Auth;

use App\Models\Access\Client;
use App\Models\Alert;
use App\Models\Billing\Quota;
use App\Models\Billing\Strike;
use App\Models\Content\Avatar;
use App\Models\Game\Player;
use App\Models\Gamification\Badge;
use App\Models\Gamification\GlobalRank;
use App\Models\Gamification\Point;
use App\Models\Gamification\UserTitleLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Billable, HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'agent_id',
        'avatar_id',
        'bio',
        'social_links',
        'deactivated_at',
        'registration_client_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'social_links' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->username)) {
                // Generate temporary username, will be updated after creation
                $user->username = 'player'.str_pad((string) rand(1, 9999999), 7, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($user) {
            // Update with proper username based on ID
            $properUsername = self::generateUsername($user->id);
            if ($user->username !== $properUsername) {
                $user->username = $properUsername;
                $user->saveQuietly(); // Save without triggering events again
            }
        });
    }

    /**
     * Generate a username in format: player0000000
     * Takes user ID, adds 69420, and pads with leading zeros to 7 digits
     */
    public static function generateUsername(int $userId): string
    {
        $number = $userId + 69420;
        $paddedNumber = str_pad((string) $number, 7, '0', STR_PAD_LEFT);

        return 'player'.$paddedNumber;
    }

    // Relationships
    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Avatar::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function strikes(): HasMany
    {
        return $this->hasMany(Strike::class);
    }

    public function quotas(): HasMany
    {
        return $this->hasMany(Quota::class);
    }

    public function points(): HasMany
    {
        return $this->hasMany(Point::class);
    }

    public function globalRank(): HasOne
    {
        return $this->hasOne(GlobalRank::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badge')
            ->withPivot('earned_at');
    }

    public function titleLevels(): HasMany
    {
        return $this->hasMany(UserTitleLevel::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function registrationClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'registration_client_id');
    }

    // Helper methods
    public function isAgent(): bool
    {
        return $this->agent_id !== null;
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null && ! $this->trashed();
    }

    public function initials(): string
    {
        $names = explode(' ', $this->name);
        $initials = '';

        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }

        return $initials;
    }

    /**
     * Check if user can update their username.
     * Requires the 'can-update-username' permission.
     */
    public function canUpdateUsername(): bool
    {
        return $this->hasPermissionTo('can-update-username');
    }
}
