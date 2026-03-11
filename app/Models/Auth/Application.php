<?php

namespace App\Models\Auth;

use Database\Factories\Auth\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;
}
