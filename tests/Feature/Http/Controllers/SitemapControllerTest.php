<?php

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

it('serves the sitemaps as XML, generating them when none exist yet', function (string $path) {
    Article::factory()->published()->for(Category::factory()->create(['slug' => 'podosfairo']))->create(['slug' => 'arthro']);

    $this->get($path)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<?xml', false);
})->with([
    'index' => '/sitemap.xml',
    'news' => '/sitemap-news.xml',
    'static' => '/sitemaps/static.xml',
    'articles' => '/sitemaps/articles-1.xml',
]);

it('returns 404 for sitemap files that do not exist', function (string $path) {
    $this->get($path)->assertNotFound();
})->with([
    'article file beyond the last one' => '/sitemaps/articles-5.xml',
    'unknown name' => '/sitemaps/index.xml',
    'path traversal' => '/sitemaps/..%2F..%2F.env.xml',
]);

it('lets search engines crawl the public site in production and points them to the sitemaps', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap: '.route('sitemaps.index'))
        ->assertDontSee("Disallow: /\n", false);
});

it('blocks all crawling outside production', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /')
        ->assertDontSee('Sitemap:');
});

it('shows a styled, non-indexed 404 page without a canonical URL', function () {
    $this->get('/den-yparxei')
        ->assertNotFound()
        ->assertSee(__('Page not found'))
        ->assertSee('<meta name="robots" content="noindex" />', false)
        ->assertDontSee('rel="canonical"', false);
});
