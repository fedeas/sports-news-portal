<?php

use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows a match with its teams, score and competition', function () {
    $season = Season::factory()->for(League::factory()->create(['name' => 'Πρωτάθλημα Κύπρου']))->create(['name' => '2025/2026']);
    $fixture = Fixture::factory()->for($season)->finished(2, 1)->create([
        'home_team_id' => Team::factory()->create(['name' => 'Ολυμπιάδα Λευκωσίας'])->id,
        'away_team_id' => Team::factory()->create(['name' => 'Νέα Πάφος'])->id,
        'venue' => 'Στάδιο Επίδειξης',
    ]);

    $this->get(route('fixtures.show', $fixture))
        ->assertOk()
        ->assertSee('Ολυμπιάδα Λευκωσίας')
        ->assertSee('Νέα Πάφος')
        ->assertSee('2 – 1')
        ->assertSee('Πρωτάθλημα Κύπρου')
        ->assertSee('Στάδιο Επίδειξης')
        ->assertSee('<link rel="canonical" href="'.route('fixtures.show', $fixture).'" />', false);
});

it('returns 404 for matches of inactive leagues and unknown matches', function () {
    $fixture = Fixture::factory()->for(Season::factory()->for(League::factory()->inactive()))->create();

    $this->get(route('fixtures.show', $fixture))->assertNotFound();
    $this->get('/fixtures/999999')->assertNotFound();
});
