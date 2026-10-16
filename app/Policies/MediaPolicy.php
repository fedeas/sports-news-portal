<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    /**
     * Determine whether the user can upload media. Every account is staff.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can edit the media (e.g. its alt text).
     */
    public function update(User $user, Media $media): bool
    {
        return $user->isEditorOrAbove() || $user->id === $media->uploaded_by;
    }

    /**
     * Determine whether the user can delete the media.
     */
    public function delete(User $user, Media $media): bool
    {
        return $user->isEditorOrAbove() || $user->id === $media->uploaded_by;
    }
}
