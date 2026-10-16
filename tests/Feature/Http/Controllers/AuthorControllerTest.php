<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows a writer with their own and their co-authored articles', function () {
    $author = Author::factory()->create(['name' => 'Ελένη Κωνσταντίνου', 'slug' => 'eleni', 'bio' => 'Αθλητική συντάκτρια.']);
    Article::factory()->published()->for($author)->create(['title' => 'Δικό της άρθρο']);
    $coAuthored = Article::factory()->published()->create(['title' => 'Συνεργασία']);
    $coAuthored->coAuthors()->attach($author->id, ['sort_order' => 0]);
    Article::factory()->published()->create(['title' => 'Άρθρο άλλου']);
    Article::factory()->for($author)->create(['title' => 'Πρόχειρο δικό της']);

    $this->get('/authors/eleni')
        ->assertOk()
        ->assertSee('Ελένη Κωνσταντίνου')
        ->assertSee('Αθλητική συντάκτρια.')
        ->assertSee('Δικό της άρθρο')
        ->assertSee('Συνεργασία')
        ->assertDontSee('Άρθρο άλλου')
        ->assertDontSee('Πρόχειρο δικό της')
        ->assertSee('"@type":"Person"', false);
});

it('hides articles in an inactive category', function () {
    $author = Author::factory()->create(['slug' => 'eleni']);
    Article::factory()->published()->for($author)->for(Category::factory()->inactive())->create(['title' => 'Κρυφό άρθρο']);

    $this->get('/authors/eleni')->assertOk()->assertDontSee('Κρυφό άρθρο');
});

it('returns 404 for an inactive or unknown writer', function (bool $exists) {
    if ($exists) {
        Author::factory()->inactive()->create(['slug' => 'eleni']);
    }

    $this->get('/authors/eleni')->assertNotFound();
})->with(['inactive' => true, 'unknown' => false]);

it('lists only writers with a live article', function () {
    $writing = Author::factory()->create(['name' => 'Γράφει Συχνά']);
    Article::factory()->published()->for($writing)->create();
    Author::factory()->create(['name' => 'Χωρίς Άρθρα']);
    $draftOnly = Author::factory()->create(['name' => 'Μόνο Πρόχειρα']);
    Article::factory()->for($draftOnly)->create();

    $this->get(route('authors.index'))
        ->assertOk()
        ->assertSee('Γράφει Συχνά')
        ->assertDontSee('Χωρίς Άρθρα')
        ->assertDontSee('Μόνο Πρόχειρα');
});

it('escapes the writer name and bio', function () {
    Author::factory()->create(['name' => '<script>alert("xss")</script>', 'slug' => 'xss', 'bio' => '<b>bio</b>']);

    $this->get('/authors/xss')
        ->assertOk()
        ->assertSee('&lt;script&gt;', false)
        ->assertDontSee('<script>alert("xss")</script>', false)
        ->assertDontSee('<b>bio</b>', false);
});

it('links only http and https social profiles', function () {
    Author::factory()->create([
        'slug' => 'eleni',
        'social_links' => [
            'facebook' => 'https://facebook.com/eleni',
            'twitter' => 'javascript:alert(1)',
            'instagram' => 'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
        ],
    ]);

    $this->get('/authors/eleni')
        ->assertOk()
        ->assertSee('https://facebook.com/eleni', false)
        ->assertDontSee('javascript:alert(1)', false)
        ->assertDontSee('data:text/html', false);
});
