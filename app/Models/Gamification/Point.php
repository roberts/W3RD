<?php

namespace App\Models\Gamification;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Point extends Model
{
    /** @use HasFactory<\Database\Factories\Gamification\PointFactory> */
    use HasFactory;

    protected $table = 'points';

    protected $fillable = [
        'user_id',
        'source_type',
        'source_id',
        'change',
        'new_total',
        'description',
    ];

    protected $casts = [
        'change' => 'integer',
        'new_total' => 'integer',
    ];

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        // Calculate new_total when creating a new ledger entry
        static::creating(function ($point) {
            // Get the most recent point ledger entry for this user
            $previousEntry = static::where('user_id', $point->user_id)
                ->orderBy('id', 'desc')
                ->first();

            // Calculate the new total
            $previousTotal = $previousEntry ? $previousEntry->new_total : 0;
            $point->new_total = $previousTotal + $point->change;
        });
    }

    // Relationships
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
