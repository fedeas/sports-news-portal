<?php

use App\Enums\ArticleStatus;
use App\Jobs\PublishScheduledArticlesJob;
use App\Models\Article;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('publishes only scheduled articles whose time has come', function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-16 10:00:00'));

    $due = Article::factory()->scheduled()->create(['published_at' => '2026-09-16 09:59:00']);
    $future = Article::factory()->scheduled()->create(['published_at' => '2026-09-16 10:01:00']);
    $draft = Article::factory()->create();

    PublishScheduledArticlesJob::dispatchSync();

    expect($due->refresh()->status)->toBe(ArticleStatus::Published)
        ->and($due->published_at->toDateTimeString())->toBe('2026-09-16 09:59:00')
        ->and($future->refresh()->status)->toBe(ArticleStatus::Scheduled)
        ->and($draft->refresh()->status)->toBe(ArticleStatus::Draft);
});
