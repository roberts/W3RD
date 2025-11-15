<?php

namespace App\Models\Gamification;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source_type',
        'source_id',
        'points',
        'balance_after',
        'description',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
    ];

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        // Calculate balance_after when creating a new ledger entry
        static::creating(function ($ledger) {
            // Get the most recent ledger entry for this user
            $previousEntry = static::where('user_id', $ledger->user_id)
                ->orderBy('id', 'desc')
                ->first();

            // Calculate the new balance
            $previousBalance = $previousEntry ? $previousEntry->balance_after : 0;
            $ledger->balance_after = $previousBalance + $ledger->points;
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function source()
    {
        return $this->morphTo();
    }
}
