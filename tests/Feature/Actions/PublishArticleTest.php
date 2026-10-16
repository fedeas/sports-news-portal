<?php

use App\Actions\PublishArticle;
use App\Enums\ArticleStatus;
use App\Models\Article;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-16 10:00:00'));
});

it('publishes a draft immediately', function () {
    $article = Article::factory()->create();

    app(PublishArticle::class)->handle($article);

    $article->refresh();

    expect($article->status)->toBe(ArticleStatus::Published)
        ->and($article->published_at->toDateTimeString())->toBe('2026-09-16 10:00:00');
});

it('schedules the article when the publish time is in the future', function () {
    $article = Article::factory()->create();

    app(PublishArticle::class)->handle($article, CarbonImmutable::parse('2026-09-20 15:30:00'));

    $article->refresh();

    expect($article->status)->toBe(ArticleStatus::Scheduled)
        ->and($article->published_at->toDateTimeString())->toBe('2026-09-20 15:30:00');
});

it('publishes with a past publish time when backdated', function () {
    $article = Article::factory()->create();

    app(PublishArticle::class)->handle($article, CarbonImmutable::parse('2026-09-15 08:00:00'));

    $article->refresh();

    expect($article->status)->toBe(ArticleStatus::Published)
        ->and($article->published_at->toDateTimeString())->toBe('2026-09-15 08:00:00');
});

it('publishes a due scheduled article at its scheduled time', function () {
    $article = Article::factory()->scheduled()->create(['published_at' => '2026-09-16 09:30:00']);

    app(PublishArticle::class)->handle($article);

    $article->refresh();

    expect($article->status)->toBe(ArticleStatus::Published)
        ->and($article->published_at->toDateTimeString())->toBe('2026-09-16 09:30:00');
});
