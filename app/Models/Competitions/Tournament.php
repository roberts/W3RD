<?php

namespace App\Models\Competitions;

use App\Models\Auth\User;
use App\Models\Games\Game;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
 * @property array<string, mixed>|null $bracket_data
 * @property array<string, mixed>|null $rules
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Game> $games
 */
class Tournament extends Model
{
    use HasUlids;

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
     * Get the route key name for Laravel route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Get the users participating in this tournament.
     *
     * @return BelongsToMany<User, $this, TournamentUser>
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
     *
     * @return HasMany<Game, $this>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * Generate ULIDs for the explicit column instead of the primary key.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Scope to find a tournament by ULID with optional eager loading.
     *
     * @param  Builder<Tournament>  $query
     * @param  array<int, string>  $with
     * @return Builder<Tournament>
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
