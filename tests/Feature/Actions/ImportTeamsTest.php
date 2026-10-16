<?php

use App\Actions\ImportTeams;
use App\Models\Season;
use App\Models\Team;
use App\Services\SportsData\Data\TeamData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates new teams in the league\'s sport and adds them to the season roster', function () {
    $season = Season::factory()->create();

    app(ImportTeams::class)->handle('api-football', [
        new TeamData('101', 'Demo Lions', 'LIO', 1930),
        new TeamData('102', 'Demo Eagles', null, null),
    ], $season);

    $lions = Team::query()->where('external_id', '101')->sole();

    expect($lions->only(['name', 'slug', 'short_name', 'founded_year', 'external_source', 'sport_id']))->toBe([
        'name' => 'Demo Lions',
        'slug' => 'demo-lions',
        'short_name' => 'LIO',
        'founded_year' => 1930,
        'external_source' => 'api-football',
        'sport_id' => $season->league->sport_id,
    ])
        ->and($season->teams()->count())->toBe(2);
});

it('keeps editorial changes to existing teams and still adds them to the roster', function () {
    $season = Season::factory()->create();
    $team = Team::factory()->create(['name' => 'Λέοντες', 'external_source' => 'api-football', 'external_id' => '101']);

    app(ImportTeams::class)->handle('api-football', [new TeamData('101', 'Demo Lions', 'LIO', 1930)], $season);

    expect(Team::query()->count())->toBe(1)
        ->and($team->refresh()->name)->toBe('Λέοντες')
        ->and($season->teams()->pluck('teams.id')->all())->toBe([$team->id]);
});
