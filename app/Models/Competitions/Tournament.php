<?php

namespace App\Models\Competitions;

use App\Models\Auth\User;
use App\Models\Games\Game;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property string $game_title
 * @property string $format
 * @property string $status
 * @property int $max_participants
 * @property int|null $buy_in_amount
 * @property string|null $buy_in_currency
 * @property int|null $prize_pool
 * @property array|null $bracket_data
 * @property array|null $rules
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Game> $games
 */
class Tournament extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'ulid',
        'name',
        'game_title',
        'format',
        'status',
        'max_participants',
        'buy_in_amount',
        'buy_in_currency',
        'prize_pool',
        'bracket_data',
        'rules',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'bracket_data' => 'array',
        'rules' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'buy_in_amount' => 'integer',
        'prize_pool' => 'integer',
    ];

    /**
     * Get the users participating in this tournament.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_user')
            ->using(TournamentUser::class)
            ->withPivot(['status', 'seed', 'placement', 'earnings'])
            ->withTimestamps();
    }

    /**
     * Get games associated with this tournament.
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Generate ULIDs for the explicit column instead of the primary key.
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Scope to find a tournament by ULID with optional eager loading.
     */
    public function scopeWithUlid($query, string $ulid, array $with = [])
    {
        $query = $query->where('ulid', $ulid);

        if (! empty($with)) {
            $query->with($with);
        }

        return $query;
    }

    /**
     * Check if tournament is accepting registrations.
     */
    public function isRegistrationOpen(): bool
    {
        return $this->status === 'registration_open'
            && $this->users()->count() < $this->max_participants
            && (! $this->starts_at || $this->starts_at->isFuture());
    }

    /**
     * Check if tournament has started.
     */
    public function hasStarted(): bool
    {
        return $this->status === 'in_progress'
            || ($this->starts_at && $this->starts_at->isPast());
    }

    /**
     * Check if tournament is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
