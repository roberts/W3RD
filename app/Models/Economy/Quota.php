<?php

namespace App\Models\Economy;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quota extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title_slug',
        'games_started',
        'reset_month',
    ];

    protected $casts = [
        'reset_month' => 'date',
        'games_started' => 'integer',
    ];

    // Relationships
    /**
     * @return BelongsTo<User, Quota>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
