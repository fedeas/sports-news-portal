<?php

use App\Models\Article;
use App\Models\Fixture;
use App\Models\Sport;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists active teams only', function () {
    Team::factory()->create(['name' => 'Ολυμπιάδα Λευκωσίας']);
    Team::factory()->inactive()->create(['name' => 'Κρυφή Ομάδα']);

    $this->get(route('teams.index'))
        ->assertOk()
        ->assertSee('Ολυμπιάδα Λευκωσίας')
        ->assertDontSee('Κρυφή Ομάδα');
});

it('shows a team with its details and SportsTeam structured data', function () {
    Team::factory()->for(Sport::factory()->create(['name' => 'Ποδόσφαιρο']))->create([
        'name' => 'Ολυμπιάδα Λευκωσίας',
        'slug' => 'olympiada-lefkosias',
        'founded_year' => 1927,
    ]);

    $html = $this->get('/teams/olympiada-lefkosias')
        ->assertOk()
        ->assertSee('<h1', false)
        ->assertSee('Ολυμπιάδα Λευκωσίας')
        ->assertSee('1927')
        ->assertSee('<link rel="canonical" href="'.url('/teams/olympiada-lefkosias').'" />', false)
        ->getContent();

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
    $data = json_decode($matches[1] ?? '', true);

    expect($data['@type'])->toBe('SportsTeam')
        ->and($data['name'])->toBe('Ολυμπιάδα Λευκωσίας')
        ->and($data['sport'])->toBe('Ποδόσφαιρο')
        ->and($data['foundingDate'])->toBe('1927');
});

it('lists live articles about the team, newest first', function () {
    $team = Team::factory()->create(['slug' => 'olympiada']);
    $older = Article::factory()->published()->create(['title' => 'Παλαιότερο θέμα ομάδας', 'published_at' => now()->subDay()]);
    $newer = Article::factory()->published()->create(['title' => 'Νεότερο θέμα ομάδας', 'published_at' => now()->subHour()]);
    $draft = Article::factory()->create(['title' => 'Πρόχειρο θέμα ομάδας']);
    Article::factory()->published()->create(['title' => 'Θέμα άλλης ομάδας']);
    $team->articles()->attach([$older->id, $newer->id, $draft->id]);

    $this->get('/teams/olympiada')
        ->assertOk()
        ->assertSeeInOrder(['Νεότερο θέμα ομάδας', 'Παλαιότερο θέμα ομάδας'])
        ->assertDontSee('Πρόχειρο θέμα ομάδας')
        ->assertDontSee('Θέμα άλλης ομάδας');
});

it('shows the team\'s upcoming fixtures and latest results', function () {
    $team = Team::factory()->create(['slug' => 'olympiada', 'name' => 'Ολυμπιάδα Λευκωσίας']);
    Fixture::factory()->finished(2, 1)->create(['home_team_id' => $team->id, 'away_team_id' => Team::factory()->create(['name' => 'Αστέρας Λάρνακας'])->id]);
    Fixture::factory()->create(['away_team_id' => $team->id, 'home_team_id' => Team::factory()->create(['name' => 'Νέα Πάφος'])->id]);
    Fixture::factory()->finished(4, 4)->create(['home_team_id' => Team::factory()->create(['name' => 'Άσχετη Ομάδα'])->id]);

    $this->get('/teams/olympiada')
        ->assertOk()
        ->assertSee('2 – 1')
        ->assertSee('Αστέρας Λάρνακας')
        ->assertSee('Νέα Πάφος')
        ->assertDontSee('Άσχετη Ομάδα');
});

it('returns 404 for unknown and inactive teams', function () {
    Team::factory()->inactive()->create(['slug' => 'krymmeni']);

    $this->get('/teams/krymmeni')->assertNotFound();
    $this->get('/teams/den-yparxei')->assertNotFound();
});

it('redirects an old team slug to the current URL', function () {
    $team = Team::factory()->create(['slug' => 'olympiada']);

    $team->update(['slug' => 'olympiada-lefkosias']);

    $this->get('/teams/olympiada')->assertStatus(301)->assertRedirect('/teams/olympiada-lefkosias');
});

it('escapes the team name', function () {
    Team::factory()->create(['slug' => 'omada', 'name' => '<script>alert("xss")</script>']);

    $this->get('/teams/omada')
        ->assertOk()
        ->assertDontSee('<script>alert("xss")</script>', false);
});
