<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the live articles on a topic, newest first', function () {
    $tag = Tag::factory()->create(['name' => 'ΑΠΟΕΛ', 'slug' => 'apoel']);
    $older = Article::factory()->published()->create(['title' => 'Παλιό θέμα', 'published_at' => now()->subDays(2)]);
    $newer = Article::factory()->published()->create(['title' => 'Νέο θέμα', 'published_at' => now()->subHour()]);
    $draft = Article::factory()->create(['title' => 'Πρόχειρο θέμα']);
    $other = Article::factory()->published()->create(['title' => 'Άσχετο θέμα']);
    $tag->articles()->attach([$older->id, $newer->id, $draft->id]);

    $this->get('/tags/apoel')
        ->assertOk()
        ->assertSee('ΑΠΟΕΛ')
        ->assertSeeInOrder(['Νέο θέμα', 'Παλιό θέμα'])
        ->assertDontSee('Πρόχειρο θέμα')
        ->assertDontSee($other->title);
});

it('hides articles in an inactive category', function () {
    $tag = Tag::factory()->create(['slug' => 'apoel']);
    $hidden = Article::factory()->published()->for(Category::factory()->inactive())->create(['title' => 'Κρυφό θέμα']);
    $tag->articles()->attach($hidden->id);

    $this->get('/tags/apoel')->assertOk()->assertDontSee('Κρυφό θέμα');
});

it('returns 404 for an unknown topic', function () {
    $this->get('/tags/den-yparxei')->assertNotFound();
});

it('escapes the topic name', function () {
    Tag::factory()->create(['name' => '<script>alert("xss")</script>', 'slug' => 'xss']);

    $this->get('/tags/xss')
        ->assertOk()
        ->assertSee('&lt;script&gt;', false)
        ->assertDontSee('<script>alert("xss")</script>', false);
});
