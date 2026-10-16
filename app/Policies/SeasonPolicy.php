<?php

namespace App\Policies;

use App\Models\Season;
use App\Models\User;

class SeasonPolicy
{
    /**
     * Determine whether the user can list seasons in the admin panel.
     */
    public function viewAny(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can create seasons.
     */
    public function create(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can update the season.
     */
    public function update(User $user, Season $season): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can delete the season.
     */
    public function delete(User $user, Season $season): bool
    {
        return $user->isEditorOrAbove();
    }
}
