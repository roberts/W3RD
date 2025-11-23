<?php

namespace App\Models\Economy;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
