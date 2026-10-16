<?php

namespace App\Jobs;

use App\Actions\RecordArticleView;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FlushArticleViewsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Where the buffer is moved while it is written, so views recorded meanwhile start a new buffer.
     */
    public const PROCESSING_KEY = 'article-views:processing';

    /**
     * Write the views buffered in Redis to the articles' view counters.
     *
     * The buffer is renamed before it is read, so no view is counted twice or lost. A batch left over
     * from a failed run is written first. Nothing happens unless the cache store is Redis.
     */
    public function handle(): void
    {
        $store = Cache::getStore();

        if (! $store instanceof RedisStore) {
            return;
        }

        $redis = $store->connection();

        if (! $redis->exists(self::PROCESSING_KEY)) {
            if (! $redis->exists(RecordArticleView::BUFFER_KEY)) {
                return;
            }

            $redis->rename(RecordArticleView::BUFFER_KEY, self::PROCESSING_KEY);
        }

        foreach ($redis->hgetall(self::PROCESSING_KEY) as $articleId => $views) {
            DB::table('articles')->where('id', (int) $articleId)->increment('view_count', (int) $views);
        }

        $redis->del(self::PROCESSING_KEY);
    }
}
