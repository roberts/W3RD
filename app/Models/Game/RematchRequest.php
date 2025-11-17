<?php

namespace App\Models\Game;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RematchRequest extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'original_game_id',
        'requesting_user_id',
        'opponent_user_id',
        'new_game_id',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function originalGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'original_game_id');
    }

    public function requestingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requesting_user_id');
    }

    public function opponentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opponent_user_id');
    }

    public function newGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'new_game_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }
}
