<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can list accounts in the admin panel.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create accounts.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can change another account's role.
     *
     * Admins cannot change their own role, so the last admin can't lock everyone out.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }
}
