<?php

use App\Http\Controllers\SearchSuggestionController;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;

// A MySQL FULLTEXT index does not see rows from an uncommitted transaction, so MySQL runs truncate instead.
uses(env('DB_CONNECTION') === 'mysql' ? DatabaseTruncation::class : RefreshDatabase::class);

afterEach(function () {
    if (env('DB_CONNECTION') === 'mysql') {
        $this->truncateDatabaseTables();
    }
});

it('suggests live stories and active clubs that match what has been typed', function () {
    $writer = Author::factory()->create(['name' => 'Μαρία Γεωργίου']);
    $section = Category::factory()->create(['name' => 'Ποδόσφαιρο']);
    $article = Article::factory()->published()->for($writer)->for($section)->create(['title' => 'Ολυμπιάδα: νίκη στο ντέρμπι']);
    Article::factory()->create(['title' => 'Ολυμπιάδα: πρόχειρο']);
    Article::factory()->published()->for(Category::factory()->inactive())->create(['title' => 'Ολυμπιάδα: κρυφό']);
    $team = Team::factory()->create(['name' => 'Ολυμπιάδα Λευκωσίας']);
    Team::factory()->inactive()->create(['name' => 'Ολυμπιάδα Παλιά']);

    $this->getJson(route('search.suggestions', ['q' => 'Ολυμπ']))
        ->assertOk()
        ->assertExactJson([
            'articles' => [[
                'title' => 'Ολυμπιάδα: νίκη στο ντέρμπι',
                'url' => $article->url(),
                'category' => 'Ποδόσφαιρο',
                'author' => 'Μαρία Γεωργίου',
                'image' => $article->featuredImage?->smallestUrl(),
            ]],
            'teams' => [['name' => 'Ολυμπιάδα Λευκωσίας', 'url' => $team->url()]],
        ]);
});

it('suggests nothing until enough has been typed', function (string $term) {
    Article::factory()->published()->create(['title' => 'Ομόνοια: νίκη']);

    $this->getJson(route('search.suggestions', ['q' => $term]))
        ->assertOk()
        ->assertExactJson(['articles' => [], 'teams' => []]);
})->with(['empty' => '', 'one letter' => 'Ο', 'only spaces' => '   ']);

it('keeps the number of suggestions short', function () {
    Article::factory()->published()->count(SearchSuggestionController::ARTICLES + 2)->create(['title' => 'Ομόνοια: νέα']);
    Team::factory()->count(SearchSuggestionController::TEAMS + 2)->sequence(fn ($sequence) => ['name' => "Ομόνοια {$sequence->index}"])->create();

    $this->getJson(route('search.suggestions', ['q' => 'Ομόνοια']))
        ->assertOk()
        ->assertJsonCount(SearchSuggestionController::ARTICLES, 'articles')
        ->assertJsonCount(SearchSuggestionController::TEAMS, 'teams');
});

it('matches percent signs and underscores literally in club names', function () {
    Team::factory()->create(['name' => 'Ανόρθωση']);

    $this->getJson(route('search.suggestions', ['q' => '%_']))
        ->assertOk()
        ->assertJsonCount(0, 'teams');
});

it('rejects a term that is not a string', function () {
    $this->getJson(route('search.suggestions').'?q[]=Ομόνοια')->assertUnprocessable();
});

it('rate limits suggestions per visitor', function () {
    foreach (range(1, 120) as $attempt) {
        $this->getJson(route('search.suggestions', ['q' => 'Ομ']))->assertOk();
    }

    $this->getJson(route('search.suggestions', ['q' => 'Ομ']))->assertTooManyRequests();
});
