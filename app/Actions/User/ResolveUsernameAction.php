<?php

namespace App\Actions\User;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ResolveUsernameAction
{
    /**
     * Resolve a username to a User model.
     *
     * @throws ModelNotFoundException
     */
    public function execute(string $username): User
    {
        return User::where('username', strtolower($username))->firstOrFail();
    }

    /**
     * Resolve a username to a User model, returning null if not found.
     */
    public function executeOrNull(string $username): ?User
    {
        return User::where('username', strtolower($username))->first();
    }
}
