<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Media;
use App\Models\Season;
use App\Models\Team;
use App\Services\ContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Cache a marker under each content tag and report which markers survive the callback.
 *
 * @return array<int, string>
 */
function contentTagsSurviving(Closure $callback): array
{
    $tags = [ContentCache::ARTICLES, ContentCache::CATEGORIES, ContentCache::FIXTURES];
    $cache = app(ContentCache::class);

    foreach ($tags as $tag) {
        $cache->remember([$tag], 'marker', fn () => 'cached');
    }

    $callback();

    return array_values(array_filter(
        $tags,
        fn (string $tag) => $cache->remember([$tag], 'marker', fn () => 'recomputed') === 'cached',
    ));
}

it('flushes the tags a model appears in when it is saved or deleted', function (Closure $makeModel, array $survivingTags) {
    $model = $makeModel();

    expect(contentTagsSurviving(fn () => $model->touch()))->toBe($survivingTags)
        ->and(contentTagsSurviving(fn () => $model->delete()))->toBe($survivingTags);
})->with([
    'article' => [fn (): Model => Article::factory()->published()->create(), [ContentCache::CATEGORIES, ContentCache::FIXTURES]],
    'category' => [fn (): Model => Category::factory()->create(), [ContentCache::FIXTURES]],
    'media' => [fn (): Model => Media::factory()->create(), [ContentCache::CATEGORIES]],
    'team' => [fn (): Model => Team::factory()->create(), [ContentCache::ARTICLES, ContentCache::CATEGORIES]],
    'league' => [fn (): Model => League::factory()->create(), [ContentCache::ARTICLES, ContentCache::CATEGORIES]],
    'season' => [fn (): Model => Season::factory()->create(), [ContentCache::ARTICLES, ContentCache::CATEGORIES]],
    'fixture' => [fn (): Model => Fixture::factory()->create(), [ContentCache::ARTICLES, ContentCache::CATEGORIES]],
]);

it('flushes articles when a trashed article is restored', function () {
    $article = Article::factory()->published()->create();
    $article->delete();

    expect(contentTagsSurviving(fn () => $article->restore()))->toBe([ContentCache::CATEGORIES, ContentCache::FIXTURES]);
});
