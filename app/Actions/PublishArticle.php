<?php

namespace App\Actions;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Carbon\CarbonInterface;

class PublishArticle
{
    /**
     * Publish the article, or schedule it when the publish time is in the future.
     *
     * Without an explicit time, a scheduled article is published at its scheduled time
     * and any other article is published now.
     */
    public function handle(Article $article, ?CarbonInterface $publishAt = null): Article
    {
        $publishAt ??= ($article->status === ArticleStatus::Scheduled && $article->published_at !== null)
            ? $article->published_at
            : now();

        $article->status = $publishAt->isFuture() ? ArticleStatus::Scheduled : ArticleStatus::Published;
        $article->published_at = $publishAt;
        $article->save();

        return $article;
    }
}
