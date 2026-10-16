<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the hero, breaking news, latest news and section blocks', function () {
    $football = Category::factory()->create(['name' => 'Ποδόσφαιρο']);

    Article::factory()->published()->featured()->for($football)->create(['title' => 'Κεντρικό θέμα', 'published_at' => now()->subHours(5)]);
    Article::factory()->published()->breaking()->for($football)->create(['title' => 'Έκτακτη μεταγραφή', 'published_at' => now()->subHour()]);
    Article::factory()->published()->for($football)->create(['title' => 'Νέα από την προπόνηση', 'published_at' => now()->subHours(2)]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Κεντρικό θέμα')
        ->assertSee('Έκτακτη μεταγραφή')
        ->assertSee('Νέα από την προπόνηση')
        ->assertSee('Ποδόσφαιρο')
        ->assertSee('<link rel="canonical" href="'.route('home').'" />', false);
});

it('signs every story with its writer', function () {
    $lead = Author::factory()->create(['name' => 'Ανδρέας Χαραλάμπους']);
    $second = Author::factory()->create(['name' => 'Ελένη Νικολάου']);
    Article::factory()->published()->featured()->for($lead)->create();
    Article::factory()->published()->for($second)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Ανδρέας Χαραλάμπους')
        ->assertSee('Ελένη Νικολάου')
        ->assertSee('href="'.$second->url().'"', false);
});

it('describes the site and its search to search engines', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
    $data = json_decode($matches[1] ?? '', true);

    expect($data['@type'])->toBe('WebSite')
        ->and($data['potentialAction']['target']['urlTemplate'])->toBe(route('search').'?q={search_term_string}');
});

it('shows a message when nothing has been published', function () {
    Article::factory()->create(['title' => 'Πρόχειρο άρθρο']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(__('No articles have been published yet.'))
        ->assertDontSee('Πρόχειρο άρθρο');
});

it('escapes article titles', function () {
    Article::factory()->published()->breaking()->create(['title' => '<script>alert("xss")</script>']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('&lt;script&gt;', false)
        ->assertDontSee('<script>alert("xss")</script>', false);
});

it('shows the most read articles when some have been read', function () {
    Article::factory()->published()->create(['title' => 'Το πιο διαβασμένο', 'view_count' => 120]);
    Article::factory()->published()->create(['title' => 'Χωρίς προβολές', 'view_count' => 0]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(__('Most read'))
        ->assertSee('Το πιο διαβασμένο');
});

it('hides the most read block while nothing has been read', function () {
    Article::factory()->published()->create(['view_count' => 0]);

    $this->get(route('home'))->assertOk()->assertDontSee(__('Most read'));
});

it('shows the league table and the next matches beside the news', function () {
    $league = League::factory()->create(['name' => 'Πρωτάθλημα Α΄ Κατηγορίας', 'slug' => 'protathlima']);
    $season = Season::factory()->current()->for($league)->create();
    $home = Team::factory()->create(['name' => 'Ομόνοια Λευκωσίας', 'short_name' => 'ΟΜΟ']);
    $away = Team::factory()->create(['name' => 'ΑΠΟΕΛ', 'short_name' => 'ΑΠΟΕΛ']);
    $season->teams()->attach([$home->id, $away->id]);
    Fixture::factory()->for($season)->finished(1, 0)->create(['home_team_id' => $home->id, 'away_team_id' => $away->id, 'kickoff_at' => now()->subWeek()]);
    Fixture::factory()->for($season)->create(['home_team_id' => $away->id, 'away_team_id' => $home->id, 'kickoff_at' => now()->addDay()]);
    Article::factory()->published()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Πρωτάθλημα Α΄ Κατηγορίας')
        ->assertSee(__('Next matches'))
        ->assertSee('ΟΜΟ')
        ->assertSee(__('Full table'));
});
