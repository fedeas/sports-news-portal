<?php

namespace App\Policies;

use App\Models\Author;
use App\Models\User;

class AuthorPolicy
{
    /**
     * Determine whether the user can list authors in the admin panel.
     */
    public function viewAny(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can create authors.
     */
    public function create(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can update the author.
     */
    public function update(User $user, Author $author): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can delete the author.
     */
    public function delete(User $user, Author $author): bool
    {
        return $user->isEditorOrAbove();
    }
}
