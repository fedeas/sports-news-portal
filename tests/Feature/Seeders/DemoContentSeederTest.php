<?php

use App\Models\Article;
use App\Models\Media;
use App\Models\User;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Queue::fake();
});

it('creates live demo articles with placeholder photos that the homepage can render', function () {
    User::factory()->admin()->create();

    $this->seed(DemoContentSeeder::class);

    expect(Article::query()->count())->toBe(DemoContentSeeder::ARTICLE_COUNT)
        ->and(Article::query()->published()->count())->toBe(DemoContentSeeder::ARTICLE_COUNT - 2)
        ->and(Media::query()->whereNull('variants')->count())->toBe(0);

    $this->get(route('home'))->assertOk();
});

it('does not add demo content when articles already exist', function () {
    User::factory()->admin()->create();
    Article::factory()->create();

    $this->seed(DemoContentSeeder::class);

    expect(Article::query()->count())->toBe(1);
});
