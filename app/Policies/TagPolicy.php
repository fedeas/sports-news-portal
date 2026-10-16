<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    /**
     * Determine whether the user can list tags in the admin panel.
     */
    public function viewAny(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can create tags.
     *
     * Every account is staff, and authors add new tags while writing articles.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can rename the tag.
     */
    public function update(User $user, Tag $tag): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can delete the tag.
     */
    public function delete(User $user, Tag $tag): bool
    {
        return $user->isEditorOrAbove();
    }
}
