<?php

use App\Models\Article;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;

// A MySQL FULLTEXT index does not see rows from an uncommitted transaction, so MySQL runs truncate instead.
uses(env('DB_CONNECTION') === 'mysql' ? DatabaseTruncation::class : RefreshDatabase::class);

afterEach(function () {
    if (env('DB_CONNECTION') === 'mysql') {
        $this->truncateDatabaseTables();
    }
});

it('renders results for the query string and is not indexed', function () {
    Article::factory()->published()->create(['title' => 'Ολυμπιάδα: νίκη στο ντέρμπι']);

    $this->get('/search?q=Ολυμπιάδα')
        ->assertOk()
        ->assertSee('Ολυμπιάδα: νίκη στο ντέρμπι')
        ->assertSee('<meta name="robots" content="noindex, follow" />', false);
});

it('rate limits the search page', function () {
    foreach (range(1, 60) as $attempt) {
        $this->get('/search')->assertOk();
    }

    $this->get('/search')->assertTooManyRequests();
});
