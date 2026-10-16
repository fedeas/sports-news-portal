<?php

namespace App\Policies;

use App\Models\League;
use App\Models\User;

class LeaguePolicy
{
    /**
     * Determine whether the user can list leagues in the admin panel.
     */
    public function viewAny(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can create leagues.
     */
    public function create(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can update the league.
     */
    public function update(User $user, League $league): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can delete the league (and its seasons).
     */
    public function delete(User $user, League $league): bool
    {
        return $user->isEditorOrAbove();
    }
}
