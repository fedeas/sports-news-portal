<?php

namespace App\Services;

use Closure;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;

/**
 * Tagged cache for expensive public queries (homepage blocks, feeds, standings, fixture lists).
 *
 * Values are data (models and collections), never rendered HTML. Tags are flushed by model events
 * and actions whenever the underlying content changes. When the configured store cannot tag
 * (e.g. the database store in local development) the callback simply runs uncached.
 */
class ContentCache
{
    public const TTL_SECONDS = 600;

    public const ARTICLES = 'articles';

    public const CATEGORIES = 'categories';

    public const FIXTURES = 'fixtures';

    /**
     * @template TValue
     *
     * @param  array<int, string>  $tags
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(array $tags, string $key, Closure $callback, int $ttlSeconds = self::TTL_SECONDS): mixed
    {
        if (! $this->supportsTags()) {
            return $callback();
        }

        return Cache::tags($tags)->remember($key, $ttlSeconds, $callback);
    }

    /**
     * @param  array<int, string>  $tags
     */
    public function flush(array $tags): void
    {
        if ($this->supportsTags()) {
            Cache::tags($tags)->flush();
        }
    }

    private function supportsTags(): bool
    {
        return Cache::getStore() instanceof TaggableStore;
    }
}
