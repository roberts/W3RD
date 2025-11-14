<?php

namespace App\Models\Access;

use App\Enums\Platform;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\Session;

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
    public function sessions()
    {
        return $this->hasMany(Session::class);
    }
}
