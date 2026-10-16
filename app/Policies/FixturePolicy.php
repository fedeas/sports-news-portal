<?php

namespace App\Policies;

use App\Models\Fixture;
use App\Models\User;

class FixturePolicy
{
    /**
     * Determine whether the user can list fixtures in the admin panel.
     */
    public function viewAny(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can create fixtures.
     */
    public function create(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can update the fixture (including correcting its score).
     */
    public function update(User $user, Fixture $fixture): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can delete the fixture.
     */
    public function delete(User $user, Fixture $fixture): bool
    {
        return $user->isEditorOrAbove();
    }
}
