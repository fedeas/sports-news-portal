<?php

use App\Actions\SearchArticles;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;

// A MySQL FULLTEXT index does not see rows from an uncommitted transaction, so MySQL runs truncate instead.
uses(env('DB_CONNECTION') === 'mysql' ? DatabaseTruncation::class : RefreshDatabase::class);

afterEach(function () {
    if (env('DB_CONNECTION') === 'mysql') {
        $this->truncateDatabaseTables();
    }
});

it('finds live articles by title or excerpt, title matches first, then newest', function () {
    $excerptMatch = Article::factory()->published()->create(['title' => 'Νέος προπονητής', 'excerpt' => 'Η Ολυμπιάδα ανακοίνωσε αλλαγές.', 'published_at' => now()->subMinutes(5)]);
    $olderTitleMatch = Article::factory()->published()->create(['title' => 'Ολυμπιάδα: νίκη στο ντέρμπι', 'excerpt' => null, 'published_at' => now()->subDays(2)]);
    $newerTitleMatch = Article::factory()->published()->create(['title' => 'Ολυμπιάδα: νέα μεταγραφή', 'excerpt' => null, 'published_at' => now()->subDay()]);
    Article::factory()->published()->create(['title' => 'Άσχετο θέμα', 'excerpt' => 'Τίποτα σχετικό.']);

    $results = app(SearchArticles::class)->handle('Ολυμπιάδα')->get();

    expect($results->modelKeys())->toBe([$newerTitleMatch->id, $olderTitleMatch->id, $excerptMatch->id]);
});

it('never finds articles that are not live', function () {
    Article::factory()->create(['title' => 'Ολυμπιάδα πρόχειρο']);
    Article::factory()->scheduled()->create(['title' => 'Ολυμπιάδα προγραμματισμένο']);
    Article::factory()->published()->for(Category::factory()->inactive())->create(['title' => 'Ολυμπιάδα σε κρυφή κατηγορία']);

    expect(app(SearchArticles::class)->handle('Ολυμπιάδα')->get())->toBeEmpty();
});

it('treats the search term as text, not SQL', function (string $term) {
    Article::factory()->published()->count(2)->create();

    expect(app(SearchArticles::class)->handle($term)->get())->toBeEmpty();
})->with([
    "' OR 1=1 --",
    '") OR ("1"="1',
]);

it('finds articles by the start of each word, in any order', function (string $term) {
    $article = Article::factory()->published()->create(['title' => 'Ολυμπιάδα Λευκωσίας: νίκη στο ντέρμπι']);
    Article::factory()->published()->create(['title' => 'Ολυμπιακός: ήττα εκτός έδρας']);

    expect(app(SearchArticles::class)->handle($term)->get()->modelKeys())->toBe([$article->id]);
})->with([
    'word prefix' => 'Ολυμπιάδ',
    'two words' => 'ντέρμπι Ολυμπιάδα',
]);
