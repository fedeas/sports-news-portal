<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determine whether the user can list teams in the admin panel.
     */
    public function viewAny(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can create teams.
     */
    public function create(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can update the team.
     */
    public function update(User $user, Team $team): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can delete the team.
     */
    public function delete(User $user, Team $team): bool
    {
        return $user->isEditorOrAbove();
    }
}
