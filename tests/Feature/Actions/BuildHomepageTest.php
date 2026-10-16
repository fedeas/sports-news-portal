<?php

use App\Actions\BuildHomepage;
use App\Models\Article;
use App\Models\Category;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-16 12:00:00'));
});

describe('hero', function () {
    it('is the most recent featured article', function () {
        Article::factory()->published()->featured()->create(['published_at' => now()->subDays(2)]);
        $newestFeatured = Article::factory()->published()->featured()->create(['published_at' => now()->subHours(3)]);
        Article::factory()->published()->breaking()->create(['published_at' => now()->subHour()]);
        Article::factory()->published()->create(['published_at' => now()->subMinutes(5)]);

        expect(app(BuildHomepage::class)->hero()->is($newestFeatured))->toBeTrue();
    });

    it('falls back to recent breaking news when nothing is featured', function () {
        $breaking = Article::factory()->published()->breaking()->create(['published_at' => now()->subHours(3)]);
        Article::factory()->published()->create(['published_at' => now()->subMinutes(5)]);

        expect(app(BuildHomepage::class)->hero()->is($breaking))->toBeTrue();
    });

    it('falls back to the latest article when nothing is featured or recently breaking', function () {
        Article::factory()->published()->breaking()->create(['published_at' => now()->subHours(30)]);
        $latest = Article::factory()->published()->create(['published_at' => now()->subHour()]);

        expect(app(BuildHomepage::class)->hero()->is($latest))->toBeTrue();
    });
});

it('shows only breaking news from the last 24 hours, newest first', function () {
    $older = Article::factory()->published()->breaking()->create(['published_at' => now()->subHours(20)]);
    $newer = Article::factory()->published()->breaking()->create(['published_at' => now()->subHour()]);
    Article::factory()->published()->breaking()->create(['published_at' => now()->subHours(25)]);
    Article::factory()->published()->create(['published_at' => now()->subMinutes(5)]);

    expect(app(BuildHomepage::class)->breaking()->modelKeys())->toBe([$newer->id, $older->id]);
});

it('lists the latest nine articles without the hero, newest first', function () {
    $hero = Article::factory()->published()->featured()->create(['published_at' => now()->subDays(3)]);
    $articles = Article::factory()->published()->count(10)->sequence(
        fn ($sequence) => ['published_at' => now()->subMinutes($sequence->index + 1)],
    )->create();

    $latest = app(BuildHomepage::class)->latest($hero);

    expect($latest->modelKeys())->toBe($articles->take(9)->modelKeys())
        ->and($latest->contains($hero))->toBeFalse();
});

it('builds a block per section with its latest articles, including subcategories, in section order', function () {
    $basketball = Category::factory()->create(['sort_order' => 2]);
    $football = Category::factory()->create(['sort_order' => 1]);
    $cyprus = Category::factory()->childOf($football)->create();

    $fromSubcategory = Article::factory()->published()->for($cyprus)->create(['published_at' => now()->subHour()]);
    $fromSection = Article::factory()->published()->for($football)->create(['published_at' => now()->subHours(2)]);
    Article::factory()->published()->for($football)->count(3)->create(['published_at' => now()->subDays(1)]);
    $basketballArticle = Article::factory()->published()->for($basketball)->create();

    $blocks = app(BuildHomepage::class)->categoryBlocks();

    expect($blocks->map(fn (array $block) => $block['category']->id)->all())->toBe([$football->id, $basketball->id])
        ->and($blocks[0]['articles'])->toHaveCount(4)
        ->and(array_slice($blocks[0]['articles']->modelKeys(), 0, 2))->toBe([$fromSubcategory->id, $fromSection->id])
        ->and($blocks[1]['articles']->modelKeys())->toBe([$basketballArticle->id]);
});

it('leaves out empty and inactive sections and their articles', function () {
    Category::factory()->create();
    $hidden = Category::factory()->inactive()->create();
    $hiddenArticle = Article::factory()->published()->for($hidden)->create();

    $homepage = app(BuildHomepage::class)->handle();

    expect($homepage['categoryBlocks'])->toBeEmpty()
        ->and($homepage['hero'])->toBeNull()
        ->and($homepage['latest']->contains($hiddenArticle))->toBeFalse();
});

it('never shows articles that are not live', function (string $state) {
    $factory = Article::factory()->featured()->breaking();

    match ($state) {
        'draft' => $factory->create(),
        'scheduled' => $factory->scheduled()->create(['published_at' => now()->addHour()]),
        'archived' => $factory->archived()->create(['published_at' => now()->subHour()]),
        'trashed' => $factory->published()->create()->delete(),
    };

    $homepage = app(BuildHomepage::class)->handle();

    expect($homepage['hero'])->toBeNull()
        ->and($homepage['breaking'])->toBeEmpty()
        ->and($homepage['latest'])->toBeEmpty()
        ->and($homepage['categoryBlocks'])->toBeEmpty();
})->with(['draft', 'scheduled', 'archived', 'trashed']);

describe('caching', function () {
    it('keeps the chosen articles until an article is saved', function () {
        $first = Article::factory()->published()->create(['published_at' => now()->subHour()]);
        app(BuildHomepage::class)->handle();
        $second = Article::withoutEvents(fn () => Article::factory()->published()->create(['published_at' => now()->subMinute()]));

        $cached = app(BuildHomepage::class)->handle();
        $second->update(['title' => 'Νέος τίτλος']);
        $refreshed = app(BuildHomepage::class)->handle();

        expect($cached['hero']->is($first))->toBeTrue()
            ->and($refreshed['hero']->is($second))->toBeTrue();
    });

    it('shows the current title of a cached article', function () {
        $article = Article::factory()->published()->create(['title' => 'Παλιός τίτλος']);
        app(BuildHomepage::class)->handle();

        Article::query()->whereKey($article->id)->toBase()->update(['title' => 'Διορθωμένος τίτλος']);

        expect(app(BuildHomepage::class)->handle()['hero']->title)->toBe('Διορθωμένος τίτλος');
    });

    it('drops a cached article that is no longer live', function () {
        $article = Article::factory()->published()->breaking()->for(Category::factory())->create();
        app(BuildHomepage::class)->handle();

        Article::query()->whereKey($article->id)->toBase()->update(['deleted_at' => now()]);
        $homepage = app(BuildHomepage::class)->handle();

        expect($homepage['hero'])->toBeNull()
            ->and($homepage['breaking'])->toBeEmpty()
            ->and($homepage['latest'])->toBeEmpty()
            ->and($homepage['categoryBlocks'])->toBeEmpty();
    });
});

describe('most read', function () {
    it('ranks the most read live articles of the last month', function () {
        $second = Article::factory()->published()->create(['view_count' => 40, 'published_at' => now()->subDays(2)]);
        $first = Article::factory()->published()->create(['view_count' => 90, 'published_at' => now()->subDay()]);
        $old = Article::factory()->published()->create(['view_count' => 500, 'published_at' => now()->subDays(31)]);
        $unread = Article::factory()->published()->create(['view_count' => 0]);
        $draft = Article::factory()->create(['view_count' => 900]);

        $mostRead = app(BuildHomepage::class)->handle()['mostRead'];

        expect($mostRead->modelKeys())->toBe([$first->id, $second->id])
            ->and($mostRead->contains($old))->toBeFalse()
            ->and($mostRead->contains($unread))->toBeFalse()
            ->and($mostRead->contains($draft))->toBeFalse();
    });

    it('lists at most five articles', function () {
        Article::factory()->published()->count(7)->sequence(fn ($sequence) => ['view_count' => 10 + $sequence->index])->create();

        expect(app(BuildHomepage::class)->handle()['mostRead'])->toHaveCount(BuildHomepage::MOST_READ_LIMIT);
    });
});
