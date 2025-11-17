<?php

namespace App\Models\Game;

use App\Enums\LobbyPlayerStatus;
use App\Models\Auth\User;
use Database\Factories\Game\LobbyPlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LobbyPlayer extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return LobbyPlayerFactory::new();
    }

    protected $fillable = [
        'lobby_id',
        'user_id',
        'status',
    ];

    protected $casts = [
        'status' => LobbyPlayerStatus::class,
    ];

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(Lobby::class);
    }

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
