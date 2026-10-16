<?php

namespace App\Jobs;

use App\Actions\PublishArticle;
use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishScheduledArticlesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Publish every scheduled article whose publish time has passed, keeping its scheduled time.
     */
    public function handle(PublishArticle $publishArticle): void
    {
        Article::query()
            ->where('status', ArticleStatus::Scheduled)
            ->where('published_at', '<=', now())
            ->lazyById()
            ->each(fn (Article $article) => $publishArticle->handle($article));
    }
}
