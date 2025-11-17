<?php

namespace App\Models\Game;

use App\Enums\GameTitle;
use App\Enums\LobbyStatus;
use App\Models\Auth\User;
use Database\Factories\Game\LobbyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Lobby extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return LobbyFactory::new();
    }

    protected $fillable = [
        'ulid',
        'game_title',
        'game_mode',
        'host_id',
        'is_public',
        'min_players',
        'scheduled_at',
        'status',
    ];

    protected $casts = [
        'game_title' => GameTitle::class,
        'is_public' => 'boolean',
        'min_players' => 'integer',
        'scheduled_at' => 'datetime',
        'status' => LobbyStatus::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lobby) {
            if (empty($lobby->ulid)) {
                $lobby->ulid = (string) Str::ulid();
            }
        });
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(LobbyPlayer::class);
    }

    public function acceptedPlayers(): HasMany
    {
        return $this->players()->where('status', \App\Enums\LobbyPlayerStatus::ACCEPTED);
    }

    public function isHost(User $user): bool
    {
        return $this->host_id === $user->id;
    }

    public function hasMinimumPlayers(): bool
    {
        return $this->acceptedPlayers()->count() >= $this->min_players;
    }

    public function isReady(): bool
    {
        return $this->status === LobbyStatus::READY;
    }

    public function isPending(): bool
    {
        return $this->status === LobbyStatus::PENDING;
    }

    public function markAsReady(): void
    {
        $this->update(['status' => LobbyStatus::READY]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => LobbyStatus::COMPLETED]);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => LobbyStatus::CANCELLED]);
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
