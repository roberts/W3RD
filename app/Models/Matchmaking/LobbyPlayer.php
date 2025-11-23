<?php

namespace App\Models\Matchmaking;

use App\Matchmaking\Enums\LobbyPlayerSource;
use App\Matchmaking\Enums\LobbyPlayerStatus;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $lobby_id
 * @property int $user_id
 * @property int $client_id
 * @property LobbyPlayerStatus $status
 * @property LobbyPlayerSource $source
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class LobbyPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'lobby_id',
        'user_id',
        'client_id',
        'status',
        'source',
    ];

    protected $casts = [
        'status' => LobbyPlayerStatus::class,
        'source' => LobbyPlayerSource::class,
    ];

    /**
     * Scope to find accepted lobby players.
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', LobbyPlayerStatus::ACCEPTED);
    }

    /**
     * @return BelongsTo<Lobby, LobbyPlayer>
     */
    public function lobby(): BelongsTo
    {
        return $this->belongsTo(Lobby::class);
    }

    /**
     * @return BelongsTo<User, LobbyPlayer>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accept(): void
    {
        $this->update(['status' => LobbyPlayerStatus::ACCEPTED]);
    }

    public function decline(): void
    {
        $this->update(['status' => LobbyPlayerStatus::DECLINED]);
    }

    public function isPending(): bool
    {
        return $this->status === LobbyPlayerStatus::PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === LobbyPlayerStatus::ACCEPTED;
    }

    public function isDeclined(): bool
    {
        return $this->status === LobbyPlayerStatus::DECLINED;
    }
}
