<?php

namespace App\Policies;

use App\Models\Sport;
use App\Models\User;

class SportPolicy
{
    /**
     * Determine whether the user can list sports in the admin panel.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create sports.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the sport.
     */
    public function update(User $user, Sport $sport): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the sport.
     */
    public function delete(User $user, Sport $sport): bool
    {
        return $user->isAdmin();
    }
}
