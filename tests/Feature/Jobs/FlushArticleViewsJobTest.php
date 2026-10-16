<?php

use App\Actions\RecordArticleView;
use App\Jobs\FlushArticleViewsJob;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/**
 * Whether a Redis server is reachable for the tests that need one.
 */
function redisIsReachable(): bool
{
    $socket = @fsockopen((string) config('database.redis.cache.host', '127.0.0.1'), (int) config('database.redis.cache.port', 6379), $errorCode, $errorMessage, 0.2);

    if ($socket === false) {
        return false;
    }

    fclose($socket);

    return true;
}

it('does nothing when the cache store is not Redis', function () {
    $article = Article::factory()->published()->create(['view_count' => 3]);

    (new FlushArticleViewsJob)->handle();

    expect($article->refresh()->view_count)->toBe(3);
});

describe('with Redis', function () {
    beforeEach(function () {
        config(['cache.default' => 'redis', 'database.redis.client' => 'predis', 'database.redis.options.prefix' => 'sports-news-test:']);
    });

    afterEach(function () {
        if (! redisIsReachable()) {
            return;
        }

        Cache::getStore()->connection()->del(RecordArticleView::BUFFER_KEY, FlushArticleViewsJob::PROCESSING_KEY);
    });

    it('writes buffered views to the articles and empties the buffer', function () {
        [$first, $second] = Article::factory()->published()->count(2)->create(['view_count' => 10]);
        $redis = Cache::getStore()->connection();
        $redis->hincrby(RecordArticleView::BUFFER_KEY, (string) $first->id, 3);
        $redis->hincrby(RecordArticleView::BUFFER_KEY, (string) $second->id, 1);

        (new FlushArticleViewsJob)->handle();

        expect([$first->refresh()->view_count, $second->refresh()->view_count])->toBe([13, 11])
            ->and($redis->exists(RecordArticleView::BUFFER_KEY))->toBe(0)
            ->and($redis->exists(FlushArticleViewsJob::PROCESSING_KEY))->toBe(0);
    });

    it('writes a batch left over from a failed run before starting a new one', function () {
        $article = Article::factory()->published()->create(['view_count' => 0]);
        $redis = Cache::getStore()->connection();
        $redis->hincrby(FlushArticleViewsJob::PROCESSING_KEY, (string) $article->id, 2);
        $redis->hincrby(RecordArticleView::BUFFER_KEY, (string) $article->id, 5);

        (new FlushArticleViewsJob)->handle();

        expect($article->refresh()->view_count)->toBe(2)
            ->and($redis->hget(RecordArticleView::BUFFER_KEY, (string) $article->id))->toBe('5');
    });
})->skip(fn () => ! redisIsReachable(), 'No Redis server is running.');
