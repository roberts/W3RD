<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class Avatar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_url',
        'type',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
