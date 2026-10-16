<?php

use App\Actions\RecordArticleView;
use App\Models\Article;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

/**
 * A GET request for an article page with the given headers.
 *
 * @param  array<string, string>  $headers
 */
function articleViewRequest(array $headers): Request
{
    $request = Request::create('/podosfairo/arthro');

    foreach ($headers as $name => $value) {
        $request->headers->set($name, $value);
    }

    return $request;
}

it('counts a reader view without changing the article\'s modified date', function () {
    $article = Article::factory()->published()->create(['view_count' => 4, 'updated_at' => CarbonImmutable::parse('2026-09-01 10:00:00')]);

    app(RecordArticleView::class)->handle($article, articleViewRequest(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X)']));

    $article->refresh();

    expect($article->view_count)->toBe(5)
        ->and($article->updated_at->toDateTimeString())->toBe('2026-09-01 10:00:00');
});

it('does not count crawlers, link previews or prefetches', function (array $headers) {
    $article = Article::factory()->published()->create(['view_count' => 0]);

    app(RecordArticleView::class)->handle($article, articleViewRequest($headers));

    expect($article->refresh()->view_count)->toBe(0);
})->with([
    'no user agent' => [['User-Agent' => '']],
    'search crawler' => [['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)']],
    'link preview' => [['User-Agent' => 'facebookexternalhit/1.1']],
    'command line client' => [['User-Agent' => 'curl/8.9.1']],
    'browser prefetch' => [['User-Agent' => 'Mozilla/5.0', 'Sec-Purpose' => 'prefetch']],
]);
