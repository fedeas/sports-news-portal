<?php

use App\Livewire\Search;
use App\Models\Article;
use App\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

// A MySQL FULLTEXT index does not see rows from an uncommitted transaction, so MySQL runs truncate instead.
uses(env('DB_CONNECTION') === 'mysql' ? DatabaseTruncation::class : RefreshDatabase::class);

afterEach(function () {
    if (env('DB_CONNECTION') === 'mysql') {
        $this->truncateDatabaseTables();
    }
});

beforeEach(function () {
    RateLimiter::clear('search:127.0.0.1');
});

it('shows matching articles and teams', function () {
    Article::factory()->published()->create(['title' => 'Ολυμπιάδα: νίκη στο ντέρμπι']);
    Article::factory()->published()->create(['title' => 'Άσχετο θέμα']);
    Team::factory()->create(['name' => 'Ολυμπιάδα Λευκωσίας']);

    Livewire::test(Search::class)
        ->set('query', 'Ολυμπιάδα')
        ->assertSee('Ολυμπιάδα: νίκη στο ντέρμπι')
        ->assertSee('Ολυμπιάδα Λευκωσίας')
        ->assertDontSee('Άσχετο θέμα');
});

it('asks for a longer term instead of searching', function () {
    Article::factory()->published()->create(['title' => 'Ο αγώνας']);

    Livewire::test(Search::class)
        ->set('query', 'Ο')
        ->assertSee(__('Type at least :count characters.', ['count' => Search::MIN_LENGTH]))
        ->assertDontSee('Ο αγώνας');
});

it('stops searching after too many searches in a minute', function () {
    $component = Livewire::test(Search::class);

    foreach (range(1, Search::SEARCHES_PER_MINUTE) as $attempt) {
        $component->set('query', "όρος {$attempt}");
    }

    $component->set('query', 'ένας ακόμα όρος')
        ->assertSee(__('Too many searches. Please wait a minute and try again.'));
});

it('escapes the search term in the empty-results message', function () {
    Livewire::test(Search::class)
        ->set('query', '<script>alert(1)</script>')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

it('reads the term from the query string', function () {
    Article::factory()->published()->create(['title' => 'Ολυμπιάδα: νίκη στο ντέρμπι']);

    Livewire::withQueryParams(['q' => 'Ολυμπιάδα'])
        ->test(Search::class)
        ->assertSet('query', 'Ολυμπιάδα')
        ->assertSee('Ολυμπιάδα: νίκη στο ντέρμπι');
});
