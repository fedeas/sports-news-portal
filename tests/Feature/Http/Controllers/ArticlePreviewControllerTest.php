<?php

use App\Models\Article;
use App\Models\Category;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-16 12:00:00'));
});

it('shows a draft to anyone with a signed link, marked as not live and hidden from search engines', function () {
    $article = Article::factory()->create(['title' => 'Μεταγραφή που δεν ανακοινώθηκε', 'body' => '<p>Κείμενο προσχεδίου</p>']);

    $this->get($article->previewUrl())
        ->assertOk()
        ->assertSee('Μεταγραφή που δεν ανακοινώθηκε')
        ->assertSee('Κείμενο προσχεδίου')
        ->assertSee(__('Preview: this article is not live. Anyone with this link can see it until the link expires.'))
        ->assertSee('<meta name="robots" content="noindex, nofollow" />', false)
        ->assertDontSee('application/ld+json', false)
        ->assertDontSee(__('Leave a comment'))
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('Referrer-Policy', 'no-referrer');
});

it('says when a previewed article is already live', function () {
    $article = Article::factory()->published()->for(Category::factory())->create();

    $this->get($article->previewUrl())->assertSee(__('Preview: this is how the article looks to readers.'));
});

it('rejects a preview link that is unsigned, altered or expired with 403', function (string $case) {
    $article = Article::factory()->create();
    $link = $article->previewUrl();

    $link = match ($case) {
        'unsigned' => route('articles.preview', $article),
        'another article' => str_replace("/preview/articles/{$article->id}?", '/preview/articles/'.Article::factory()->create()->id.'?', $link),
        'expired' => tap($link, fn () => $this->travel(Article::PREVIEW_LINK_HOURS * 60 + 1)->minutes()),
    };

    $this->get($link)->assertForbidden();
})->with(['unsigned', 'another article', 'expired']);

it('keeps a preview link working until it expires', function () {
    $article = Article::factory()->create();
    $link = $article->previewUrl();

    $this->travel(Article::PREVIEW_LINK_HOURS * 60 - 1)->minutes();

    $this->get($link)->assertOk();
});

it('returns 404 for a deleted article even with a valid link', function () {
    $article = Article::factory()->create();
    $link = $article->previewUrl();

    $article->delete();

    $this->get($link)->assertNotFound();
});
