<?php

use App\Http\Controllers\NewsController;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists every live story on the site, newest first, with its writer', function () {
    $writer = Author::factory()->create(['name' => 'Μαρία Γεωργίου']);
    Article::factory()->published()->create(['title' => 'Παλιό θέμα', 'published_at' => now()->subDays(3)]);
    Article::factory()->published()->for($writer)->create(['title' => 'Νέο θέμα', 'published_at' => now()->subMinutes(10)]);
    Article::factory()->create(['title' => 'Πρόχειρο θέμα']);
    Article::factory()->scheduled()->create(['title' => 'Προγραμματισμένο θέμα']);
    Article::factory()->published()->for(Category::factory()->inactive())->create(['title' => 'Κρυφό θέμα']);

    $this->get(route('news.index'))
        ->assertOk()
        ->assertSee('Ειδησεογραφία')
        ->assertSeeInOrder(['Σήμερα', 'Νέο θέμα', 'Μαρία Γεωργίου', 'Παλιό θέμα'])
        ->assertSee('href="'.$writer->url().'"', false)
        ->assertDontSee('Πρόχειρο θέμα')
        ->assertDontSee('Προγραμματισμένο θέμα')
        ->assertDontSee('Κρυφό θέμα');
});

it('pages through the feed with a canonical link per page', function () {
    Article::factory()->published()->count(NewsController::ARTICLES_PER_PAGE + 1)->create();

    $this->get(route('news.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('news.index').'?page=2" />', false);
});

it('names an inactive writer without linking to a page that does not exist', function () {
    $writer = Author::factory()->inactive()->create(['name' => 'Πρώην Συντάκτης']);
    Article::factory()->published()->for($writer)->create();

    $this->get(route('news.index'))
        ->assertOk()
        ->assertSee('Πρώην Συντάκτης')
        ->assertDontSee('href="'.$writer->url().'"', false);
});

it('shows a message when nothing has been published', function () {
    $this->get(route('news.index'))
        ->assertOk()
        ->assertSee('Δεν έχουν δημοσιευτεί ακόμα άρθρα.');
});
