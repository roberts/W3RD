<?php

namespace App\Models\Auth;

use App\Models\Access\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'email',
        'password',
        'verification_token',
        'user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'verification_token',
    ];

    /**
     * Get the client that originated the registration.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the final user record associated with the registration.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
