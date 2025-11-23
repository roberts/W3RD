<?php

namespace App\Models\Competitions;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $tournament_id
 * @property int $user_id
 * @property string $status
 * @property int $seed
 * @property int|null $placement
 * @property int|null $earnings
 */
class TournamentUser extends Pivot
{
    protected $table = 'tournament_user';

    protected $fillable = [
        'tournament_id',
        'user_id',
        'status',
        'seed',
        'placement',
        'earnings',
    ];

    protected $casts = [
        'seed' => 'integer',
        'placement' => 'integer',
        'earnings' => 'integer',
    ];
}
