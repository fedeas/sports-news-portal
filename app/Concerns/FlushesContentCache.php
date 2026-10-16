<?php

namespace App\Concerns;

use App\Services\ContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Flush the public content cache tags a model appears in whenever it is saved, deleted or restored.
 *
 * @mixin Model
 */
trait FlushesContentCache
{
    /**
     * The ContentCache tags whose cached values include this model.
     *
     * @return array<int, string>
     */
    abstract protected static function contentCacheTags(): array;

    protected static function bootFlushesContentCache(): void
    {
        $flush = fn () => app(ContentCache::class)->flush(static::contentCacheTags());

        static::saved($flush);
        static::deleted($flush);

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored($flush);
        }
    }
}
