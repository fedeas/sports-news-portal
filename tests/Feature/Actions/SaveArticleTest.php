<?php

use App\Actions\SaveArticle;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\League;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validArticleData(array $overrides = []): array
{
    return [
        'title' => 'Ομόνοια – ΑΠΟΕΛ 2-1',
        'slug' => 'omonoia-apoel-2-1',
        'excerpt' => null,
        'body' => '<p>Η Ομόνοια νίκησε.</p>',
        'category_id' => $overrides['category_id'] ?? Category::factory()->create()->id,
        'author_id' => $overrides['author_id'] ?? Author::factory()->create()->id,
        'co_author_ids' => [],
        'tag_names' => [],
        'team_ids' => [],
        'league_ids' => [],
        'is_breaking' => false,
        'is_featured' => false,
        'meta_title' => null,
        'meta_description' => null,
        'canonical_url' => null,
        'status' => ArticleStatus::Draft,
        'publish_at' => null,
        ...$overrides,
    ];
}

it('removes dangerous HTML from the body', function (string $body, string $forbidden) {
    $article = app(SaveArticle::class)->handle(new Article, validArticleData(['body' => $body]), User::factory()->create());

    expect($article->refresh()->body)->not->toContain($forbidden);
})->with([
    'script tag' => ['<p>Γκολ</p><script>alert(1)</script>', '<script'],
    'event handler' => ['<p onclick="alert(1)">Γκολ</p>', 'onclick'],
    'javascript link' => ['<a href="javascript:alert(1)">Γκολ</a>', 'javascript:'],
    'iframe' => ['<iframe src="https://evil.test"></iframe><p>Γκολ</p>', '<iframe'],
    'inline style' => ['<p style="position:fixed">Γκολ</p>', 'style='],
    'image' => ['<p>Γκολ</p><img src="https://evil.test/x.png" onerror="alert(1)">', '<img'],
]);

it('keeps the formatting the editor produces', function () {
    $body = '<h2>Το ματς</h2><p><strong>Γκολ</strong> στο <em>90΄</em> από <a href="https://example.test/teams/omonoia">τον επιθετικό</a>.</p><ul><li>Ένα</li></ul><blockquote>Δήλωση</blockquote>';

    $article = app(SaveArticle::class)->handle(new Article, validArticleData(['body' => $body]), User::factory()->create());

    expect($article->refresh()->body)->toBe($body);
});

it('creates missing tags and reuses existing ones matched ignoring accents', function () {
    Tag::factory()->create(['name' => 'ΑΠΟΕΛ', 'slug' => 'apoel']);

    $article = app(SaveArticle::class)->handle(
        new Article,
        validArticleData(['tag_names' => ['Αποέλ', 'Ανόρθωση', 'ανορθωση']]),
        User::factory()->create(),
    );

    expect($article->tags()->orderBy('slug')->pluck('slug')->all())->toBe(['anorthosi', 'apoel'])
        ->and(Tag::query()->count())->toBe(2);
});

it('stores co-authors in order without repeating the primary author', function () {
    $primary = Author::factory()->create();
    $second = Author::factory()->create();
    $third = Author::factory()->create();

    $article = app(SaveArticle::class)->handle(
        new Article,
        validArticleData(['author_id' => $primary->id, 'co_author_ids' => [$third->id, $primary->id, $second->id]]),
        User::factory()->create(),
    );

    expect($article->coAuthors()->pluck('authors.id')->all())->toBe([$third->id, $second->id]);
});

it('replaces the teams and leagues an article is about', function () {
    $oldTeam = Team::factory()->create();
    $newTeam = Team::factory()->create();
    $league = League::factory()->create();
    $user = User::factory()->create();

    $article = app(SaveArticle::class)->handle(new Article, validArticleData(['team_ids' => [$oldTeam->id]]), $user);
    app(SaveArticle::class)->handle($article, validArticleData(['team_ids' => [$newTeam->id], 'league_ids' => [$league->id]]), $user);

    expect($article->teams()->pluck('teams.id')->all())->toBe([$newTeam->id])
        ->and($article->leagues()->pluck('leagues.id')->all())->toBe([$league->id]);
});

it('records who created a new article', function () {
    $user = User::factory()->create();

    $article = app(SaveArticle::class)->handle(new Article, validArticleData(), $user);

    expect($article->refresh()->created_by)->toBe($user->id)
        ->and($article->status)->toBe(ArticleStatus::Draft);
});
