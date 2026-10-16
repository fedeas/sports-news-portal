<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\SlugHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('records the previous slug when the slug changes', function (Model $model) {
    $model->update(['slug' => 'neo-slug']);

    expect(SlugHistory::findPreviousOwner($model::class, 'palio-slug')?->is($model))->toBeTrue();
})->with([
    'article' => fn () => Article::factory()->create(['slug' => 'palio-slug']),
    'category' => fn () => Category::factory()->create(['slug' => 'palio-slug']),
]);

it('records nothing when other attributes change', function () {
    $article = Article::factory()->create(['slug' => 'palio-slug']);

    $article->update(['title' => 'Νέος τίτλος']);

    expect(SlugHistory::query()->count())->toBe(0);
});

it('points an old slug at the model that gave it up most recently', function () {
    $first = Article::factory()->create(['slug' => 'koino-slug']);
    $first->update(['slug' => 'proto-neo']);

    $second = Article::factory()->create(['slug' => 'koino-slug']);
    $second->update(['slug' => 'deftero-neo']);

    expect(SlugHistory::findPreviousOwner(Article::class, 'koino-slug')?->is($second))->toBeTrue()
        ->and(SlugHistory::query()->where('slug', 'koino-slug')->count())->toBe(1);
});

it('forgets an old slug when the model takes it back', function () {
    $article = Article::factory()->create(['slug' => 'arxiko']);

    $article->update(['slug' => 'endiameso']);
    $article->update(['slug' => 'arxiko']);

    expect(SlugHistory::query()->where('slug', 'arxiko')->exists())->toBeFalse()
        ->and(SlugHistory::findPreviousOwner(Article::class, 'endiameso')?->is($article))->toBeTrue();
});

it('keeps article and category histories separate', function () {
    $article = Article::factory()->create(['slug' => 'idio-slug']);
    $article->update(['slug' => 'arthro-neo']);

    expect(SlugHistory::findPreviousOwner(Category::class, 'idio-slug'))->toBeNull();
});
