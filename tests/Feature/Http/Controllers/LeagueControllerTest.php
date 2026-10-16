<?php

use App\Models\Article;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists active leagues only', function () {
    League::factory()->create(['name' => 'Πρωτάθλημα Κύπρου']);
    League::factory()->inactive()->create(['name' => 'Κρυφό Πρωτάθλημα']);

    $this->get(route('leagues.index'))
        ->assertOk()
        ->assertSee('Πρωτάθλημα Κύπρου')
        ->assertDontSee('Κρυφό Πρωτάθλημα');
});

it('shows a league with its seasons, newest first, marking the current one', function () {
    $league = League::factory()->create(['name' => 'Πρωτάθλημα Κύπρου', 'slug' => 'protathlima-kyprou']);
    Season::factory()->for($league)->create(['name' => '2024/2025']);
    Season::factory()->current()->for($league)->create(['name' => '2025/2026']);

    $this->get('/leagues/protathlima-kyprou')
        ->assertOk()
        ->assertSee('Πρωτάθλημα Κύπρου')
        ->assertSeeInOrder(['2025/2026', __('Current season'), '2024/2025'])
        ->assertSee('"@type":"SportsOrganization"', false);
});

it('lists live articles about the league', function () {
    $league = League::factory()->create(['slug' => 'protathlima-kyprou']);
    $live = Article::factory()->published()->create(['title' => 'Θέμα πρωταθλήματος']);
    $draft = Article::factory()->create(['title' => 'Πρόχειρο θέμα πρωταθλήματος']);
    $league->articles()->attach([$live->id, $draft->id]);

    $this->get('/leagues/protathlima-kyprou')
        ->assertOk()
        ->assertSee('Θέμα πρωταθλήματος')
        ->assertDontSee('Πρόχειρο θέμα πρωταθλήματος');
});

it('shows the current season standings, upcoming fixtures and latest results', function () {
    $league = League::factory()->create(['slug' => 'protathlima-kyprou']);
    Season::factory()->for($league)->create(['name' => '2024/2025']);
    $season = Season::factory()->current()->for($league)->create(['name' => '2025/2026']);
    $leader = Team::factory()->create(['name' => 'Ολυμπιάδα Λευκωσίας']);
    $runnerUp = Team::factory()->create(['name' => 'Αστέρας Λάρνακας']);
    $visitor = Team::factory()->create(['name' => 'Νέα Πάφος']);

    Fixture::factory()->for($season)->finished(3, 0)->create(['home_team_id' => $leader->id, 'away_team_id' => $runnerUp->id]);
    Fixture::factory()->for($season)->create(['home_team_id' => $visitor->id, 'away_team_id' => $leader->id, 'kickoff_at' => now()->addDays(3)]);

    $this->get('/leagues/protathlima-kyprou')
        ->assertOk()
        ->assertSee(__('Standings'))
        ->assertSeeInOrder(['Ολυμπιάδα Λευκωσίας', 'Αστέρας Λάρνακας'])
        ->assertSee('3 – 0')
        ->assertSee(__('Upcoming fixtures'))
        ->assertSee('Νέα Πάφος');
});

it('returns 404 for unknown and inactive leagues', function () {
    League::factory()->inactive()->create(['slug' => 'krymmeno']);

    $this->get('/leagues/krymmeno')->assertNotFound();
    $this->get('/leagues/den-yparxei')->assertNotFound();
});

it('redirects an old league slug to the current URL', function () {
    $league = League::factory()->create(['slug' => 'protathlima']);

    $league->update(['slug' => 'protathlima-kyprou']);

    $this->get('/leagues/protathlima')->assertStatus(301)->assertRedirect('/leagues/protathlima-kyprou');
});
