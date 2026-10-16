<?php

namespace App\Actions;

use App\Models\Article;
use Illuminate\Cache\RedisStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecordArticleView
{
    /**
     * Redis hash of article id => views not yet written to the database.
     */
    public const BUFFER_KEY = 'article-views:pending';

    /**
     * User agents of crawlers, link previewers and headless browsers, which are not readers.
     */
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|headless|lighthouse|curl|wget|python|http-client/i';

    /**
     * Count one reader view of an article.
     *
     * With the Redis cache store the view is buffered in a hash and written by FlushArticleViewsJob,
     * so busy articles do not lock their row on every request; otherwise the counter is incremented
     * directly. The query builder is used so `updated_at` (the article's modified date) is not touched.
     * Counting never breaks the page: a failure is reported and the view is dropped.
     */
    public function handle(Article $article, Request $request): void
    {
        if (! $this->isReader($request)) {
            return;
        }

        rescue(function () use ($article) {
            $store = Cache::getStore();

            if ($store instanceof RedisStore) {
                $store->connection()->hincrby(self::BUFFER_KEY, (string) $article->id, 1);

                return;
            }

            DB::table('articles')->where('id', $article->id)->increment('view_count');
        });
    }

    private function isReader(Request $request): bool
    {
        $userAgent = (string) $request->userAgent();

        return $userAgent !== ''
            && ! preg_match(self::BOT_PATTERN, $userAgent)
            && ! in_array('prefetch', [$request->header('Purpose'), $request->header('Sec-Purpose')], true);
    }
}
