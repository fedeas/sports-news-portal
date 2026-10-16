<?php

use App\Actions\GenerateSitemaps;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\League;
use App\Models\Tag;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo(CarbonImmutable::parse('2026-09-17 12:00:00'));
});

/**
 * @return array<int, string>
 */
function sitemapLocations(string $file): array
{
    $xml = simplexml_load_string(Storage::disk('local')->get("sitemaps/{$file}"));
    $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

    return array_map('strval', $xml->xpath('//s:loc'));
}

it('lists only live articles in the article sitemap', function () {
    $category = Category::factory()->create(['slug' => 'podosfairo']);
    Article::factory()->published()->for($category)->create(['slug' => 'dimosieumeno']);
    Article::factory()->for($category)->create(['slug' => 'proxeiro']);
    Article::factory()->scheduled()->for($category)->create(['slug' => 'programmatismeno']);
    Article::factory()->published()->for($category)->create(['slug' => 'diagrammeno'])->delete();
    Article::factory()->published()->for(Category::factory()->inactive())->create(['slug' => 'se-krymmeni-kategoria']);

    app(GenerateSitemaps::class)->handle();

    expect(sitemapLocations('articles-1.xml'))->toBe([url('/podosfairo/dimosieumeno')]);
});

it('lists the homepage, the news feed, active sections, teams and leagues in the static sitemap', function () {
    $football = Category::factory()->create(['slug' => 'podosfairo']);
    Category::factory()->childOf($football)->create(['slug' => 'kypros']);
    Category::factory()->inactive()->create(['slug' => 'krymmeni']);
    Team::factory()->create(['slug' => 'olympiada']);
    Team::factory()->inactive()->create(['slug' => 'krymmeni-omada']);
    League::factory()->create(['slug' => 'protathlima-kyprou']);

    app(GenerateSitemaps::class)->handle();

    expect(sitemapLocations('static.xml'))->toBe([
        route('home'),
        route('news.index'),
        route('teams.index'),
        route('leagues.index'),
        route('authors.index'),
        url('/podosfairo'),
        url('/podosfairo/kypros'),
        url('/teams/olympiada'),
        url('/leagues/protathlima-kyprou'),
    ]);
});

it('includes only articles from the last 48 hours in the news sitemap, with escaped titles', function () {
    $category = Category::factory()->create(['slug' => 'podosfairo']);
    Article::factory()->published()->for($category)->create(['slug' => 'prosfato', 'title' => 'ΑΠΟΕΛ & Ομόνοια <ντέρμπι>', 'published_at' => now()->subHours(3)]);
    Article::factory()->published()->for($category)->create(['slug' => 'palio', 'published_at' => now()->subHours(49)]);

    app(GenerateSitemaps::class)->handle();

    $contents = Storage::disk('local')->get('sitemaps/news.xml');
    $xml = simplexml_load_string($contents);
    $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $xml->registerXPathNamespace('news', 'http://www.google.com/schemas/sitemap-news/0.9');

    expect(sitemapLocations('news.xml'))->toBe([url('/podosfairo/prosfato')])
        ->and((string) $xml->xpath('//news:title')[0])->toBe('ΑΠΟΕΛ & Ομόνοια <ντέρμπι>')
        ->and((string) $xml->xpath('//news:publication_date')[0])->toBe('2026-09-17T09:00:00+00:00')
        ->and($contents)->toContain('ΑΠΟΕΛ &amp; Ομόνοια &lt;ντέρμπι&gt;');
});

it('splits articles into several files listed in the index and removes stale files', function () {
    $category = Category::factory()->create();
    $articles = Article::factory()->published()->for($category)->count(3)->create();

    app(GenerateSitemaps::class)->handle(urlsPerFile: 2);

    expect(sitemapLocations('index.xml'))->toBe([
        route('sitemaps.show', ['name' => 'static']),
        route('sitemaps.show', ['name' => 'articles-1']),
        route('sitemaps.show', ['name' => 'articles-2']),
    ]);

    $articles->take(2)->each->delete();

    app(GenerateSitemaps::class)->handle(urlsPerFile: 2);

    expect(Storage::disk('local')->exists('sitemaps/articles-2.xml'))->toBeFalse()
        ->and(sitemapLocations('articles-1.xml'))->toBe([$articles[2]->url()]);
});

it('lists the writer and topic pages of live articles', function () {
    $author = Author::factory()->create(['slug' => 'eleni']);
    Article::factory()->published()->for($author)->create();
    Author::factory()->create(['slug' => 'choris-arthra']);
    $tag = Tag::factory()->create(['slug' => 'apoel']);
    $tag->articles()->attach(Article::factory()->published()->create()->id);
    Tag::factory()->create(['slug' => 'achrisimopoiito']);

    app(GenerateSitemaps::class)->handle();
    $sitemap = Storage::disk('local')->get(GenerateSitemaps::DIRECTORY.'/static.xml');

    expect($sitemap)->toContain(route('authors.index'))
        ->toContain(route('authors.show', 'eleni'))
        ->toContain(route('tags.show', 'apoel'))
        ->not->toContain(route('authors.show', 'choris-arthra'))
        ->not->toContain(route('tags.show', 'achrisimopoiito'));
});
