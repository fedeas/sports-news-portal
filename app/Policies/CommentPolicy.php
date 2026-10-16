<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Determine whether the user can open the moderation queue.
     */
    public function viewAny(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can moderate (approve, reject, mark as spam) the comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->isEditorOrAbove();
    }
}
