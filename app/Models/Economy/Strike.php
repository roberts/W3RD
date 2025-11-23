<?php

namespace App\Models\Economy;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Strike extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title_slug',
        'strikes_used',
        'strike_date',
    ];

    protected $casts = [
        'strike_date' => 'date',
        'strikes_used' => 'integer',
    ];

    // Relationships
    /**
     * @return BelongsTo<User, Strike>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
