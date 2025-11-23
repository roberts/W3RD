<?php

namespace App\Models\Gamification;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalRank extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'total_points',
        'rank',
    ];

    protected $casts = [
        'total_points' => 'integer',
        'rank' => 'integer',
    ];

    // Relationships
    /**
     * @return BelongsTo<User, GlobalRank>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
