<?php

namespace App\Models\Gamification;

use App\Models\Auth\User;
use Database\Factories\Gamification\UserTitleLevelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * User progress tracking for game titles.
 *
 * Uses a composite primary key (user_id, title_slug) which Laravel doesn't
 * natively support. The array assignment to $primaryKey triggers a PHPStan
 * warning but works correctly at runtime for our use case.
 *
 * @property int $user_id
 * @property string $title_slug
 * @property int $level
 * @property int $xp_current
 * @property Carbon|null $last_played_at
 * @property User $user
 */
class UserTitleLevel extends Model
{
    /** @use HasFactory<UserTitleLevelFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title_slug',
        'level',
        'xp_current',
        'last_played_at',
    ];

    protected $casts = [
        'level' => 'integer',
        'xp_current' => 'integer',
        'last_played_at' => 'datetime',
    ];

    // Composite primary key - Laravel doesn't natively support this but it works
    /** @phpstan-ignore-next-line */
    protected $primaryKey = ['user_id', 'title_slug'];

    public $incrementing = false;

    // Relationships
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
