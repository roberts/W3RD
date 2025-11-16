<?php

namespace App\Models\Access;

use App\Enums\Platform;
use App\Models\Auth\Entry;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'api_key',
        'platform',
        'is_active',
    ];

    protected $casts = [
        'platform' => Platform::class,
        'is_active' => 'boolean',
    ];

    // Relationships
    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function registeredUsers()
    {
        return $this->hasMany(User::class, 'registration_client_id');
    }
}
