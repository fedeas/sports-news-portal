<?php

namespace App\Policies;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    /**
     * Determine whether the user can open the article list. Authors only see their own articles.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can write a new article. Authors need an author profile for the byline.
     */
    public function create(User $user): bool
    {
        return $user->isEditorOrAbove() || $user->author()->exists();
    }

    /**
     * Determine whether the user can edit the article. Authors may only edit their own drafts.
     */
    public function update(User $user, Article $article): bool
    {
        return $user->isEditorOrAbove()
            || ($article->status === ArticleStatus::Draft && $article->isOwnedBy($user));
    }

    /**
     * Determine whether the user can delete the article. Authors may only delete their own drafts.
     */
    public function delete(User $user, Article $article): bool
    {
        return $this->update($user, $article);
    }

    /**
     * Determine whether the user can publish, schedule or archive articles and mark them breaking or featured.
     */
    public function publish(User $user): bool
    {
        return $user->isEditorOrAbove();
    }
}
