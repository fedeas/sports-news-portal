<?php

use App\Http\Controllers\FeedController;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the latest live articles as RSS', function () {
    $older = Article::factory()->published()->create(['title' => 'Παλιό άρθρο', 'published_at' => now()->subDays(2)]);
    $newer = Article::factory()->published()->create(['title' => 'Νέο άρθρο', 'published_at' => now()->subHour()]);

    $response = $this->get(route('feed'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
        ->assertSeeInOrder(['Νέο άρθρο', $newer->url(), 'Παλιό άρθρο', $older->url()], false);

    $feed = simplexml_load_string($response->getContent());

    expect((string) $feed->channel->title)->toBe(config('app.name'))
        ->and((string) $feed->channel->link)->toBe(route('home'))
        ->and($feed->channel->item)->toHaveCount(2)
        ->and((string) $feed->channel->item[0]->pubDate)->toBe($newer->published_at->toRfc2822String());
});

it('leaves out articles that are not live', function (string $state) {
    $factory = Article::factory();

    match ($state) {
        'draft' => $factory->create(['title' => 'Κρυφό άρθρο']),
        'scheduled' => $factory->scheduled()->create(['title' => 'Κρυφό άρθρο']),
        'inactive category' => $factory->published()->for(Category::factory()->inactive())->create(['title' => 'Κρυφό άρθρο']),
    };

    $this->get(route('feed'))->assertOk()->assertDontSee('Κρυφό άρθρο');
})->with(['draft', 'scheduled', 'inactive category']);

it('limits the feed and escapes titles', function () {
    Article::factory()->published()->count(FeedController::ITEMS + 2)->create();
    Article::factory()->published()->create(['title' => '<script>alert("xss")</script>', 'published_at' => now()]);

    $response = $this->get(route('feed'))->assertOk();

    expect(simplexml_load_string($response->getContent())->channel->item)->toHaveCount(FeedController::ITEMS);

    $response->assertDontSee('<script>alert("xss")</script>', false);
});

it('links the feed from every public page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<link rel="alternate" type="application/rss+xml"', false)
        ->assertSee(route('feed'), false);
});
