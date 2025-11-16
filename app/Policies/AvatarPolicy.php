<?php

namespace App\Policies;

use App\Models\Auth\User;
use App\Models\Content\Avatar;

class AvatarPolicy
{
    /**
     * Perform pre-authorization checks.
     *
     * @param  string  $ability
     * @return void|bool
     */
    public function before(User $user, $ability)
    {
        if ($user->can('manage-everything')) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('create-avatars');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Avatar $avatar): bool
    {
        return $user->can('create-avatars');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create-avatars');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Avatar $avatar): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Avatar $avatar): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Avatar $avatar): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Avatar $avatar): bool
    {
        return false;
    }
}
