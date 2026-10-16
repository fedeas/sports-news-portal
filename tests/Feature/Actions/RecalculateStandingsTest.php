<?php

use App\Actions\RecalculateStandings;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Season;
use App\Models\Standing;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @param  array<int, string>  $names
 * @return array<string, Team>
 */
function rosterTeams(Season $season, array $names): array
{
    $teams = [];

    foreach ($names as $name) {
        $teams[$name] = Team::factory()->create(['name' => $name]);
        $season->teams()->attach($teams[$name]);
    }

    return $teams;
}

function finishedResult(Season $season, Team $home, Team $away, int $homeScore, int $awayScore, string $kickoff = '2026-09-01 18:00:00'): Fixture
{
    return Fixture::factory()->for($season)->finished($homeScore, $awayScore)->create([
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'kickoff_at' => $kickoff,
    ]);
}

/**
 * @return array<string, array<string, int|string|null>>
 */
function standingsTable(Season $season): array
{
    return $season->standings()->with('team')->get()
        ->mapWithKeys(fn (Standing $standing) => [$standing->team->name => $standing->only([
            'position', 'played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against', 'goal_difference', 'points',
        ])])
        ->all();
}

it('builds the table from finished fixtures', function () {
    $season = Season::factory()->create();
    ['Άλφα' => $alpha, 'Βήτα' => $beta, 'Γάμμα' => $gamma] = rosterTeams($season, ['Άλφα', 'Βήτα', 'Γάμμα']);

    finishedResult($season, $alpha, $beta, 2, 0);
    finishedResult($season, $beta, $gamma, 1, 1);
    finishedResult($season, $gamma, $alpha, 0, 3);

    app(RecalculateStandings::class)->handle($season);

    expect(standingsTable($season))->toBe([
        'Άλφα' => ['position' => 1, 'played' => 2, 'won' => 2, 'drawn' => 0, 'lost' => 0, 'goals_for' => 5, 'goals_against' => 0, 'goal_difference' => 5, 'points' => 6],
        'Βήτα' => ['position' => 2, 'played' => 2, 'won' => 0, 'drawn' => 1, 'lost' => 1, 'goals_for' => 1, 'goals_against' => 3, 'goal_difference' => -2, 'points' => 1],
        'Γάμμα' => ['position' => 3, 'played' => 2, 'won' => 0, 'drawn' => 1, 'lost' => 1, 'goals_for' => 1, 'goals_against' => 4, 'goal_difference' => -3, 'points' => 1],
    ]);
});

it('ignores fixtures that are not finished', function (FixtureStatus $status) {
    $season = Season::factory()->create();
    ['Άλφα' => $alpha, 'Βήτα' => $beta] = rosterTeams($season, ['Άλφα', 'Βήτα']);

    Fixture::factory()->for($season)->create([
        'home_team_id' => $alpha->id,
        'away_team_id' => $beta->id,
        'status' => $status,
        'score_home' => 2,
        'score_away' => 0,
    ]);

    app(RecalculateStandings::class)->handle($season);

    expect(collect(standingsTable($season))->pluck('played')->all())->toBe([0, 0]);
})->with([FixtureStatus::Scheduled, FixtureStatus::Live, FixtureStatus::Postponed]);

it('breaks ties on goal difference, then goals scored, then name', function () {
    $season = Season::factory()->create();
    $teams = rosterTeams($season, ['Δέλτα', 'Γάμμα', 'Βήτα', 'Άλφα', 'Ωμέγα', 'Έψιλον']);

    finishedResult($season, $teams['Άλφα'], $teams['Γάμμα'], 3, 2);
    finishedResult($season, $teams['Βήτα'], $teams['Δέλτα'], 1, 0);

    app(RecalculateStandings::class)->handle($season);

    expect(array_keys(standingsTable($season)))->toBe(['Άλφα', 'Βήτα', 'Έψιλον', 'Ωμέγα', 'Γάμμα', 'Δέλτα']);
});

it('keeps roster teams without games and removes teams no longer in the season', function () {
    $season = Season::factory()->create();
    rosterTeams($season, ['Άλφα']);
    Standing::factory()->for($season)->create(['team_id' => Team::factory()->create(['name' => 'Πρώην μέλος'])->id, 'points' => 9]);

    app(RecalculateStandings::class)->handle($season);

    expect(array_keys(standingsTable($season)))->toBe(['Άλφα']);
});

it('stores the last five results as form, oldest first', function () {
    $season = Season::factory()->create();
    ['Άλφα' => $alpha, 'Βήτα' => $beta] = rosterTeams($season, ['Άλφα', 'Βήτα']);

    foreach ([[2, 0], [1, 0], [1, 1], [0, 2], [3, 1], [2, 1]] as $index => [$alphaGoals, $betaGoals]) {
        finishedResult($season, $alpha, $beta, $alphaGoals, $betaGoals, '2026-08-0'.($index + 1).' 18:00:00');
    }

    app(RecalculateStandings::class)->handle($season);

    expect($season->standings()->where('team_id', $alpha->id)->value('form'))->toBe('WDLWW')
        ->and($season->standings()->where('team_id', $beta->id)->value('form'))->toBe('LDWLL');
});
