<?php

use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function homePoints(Fixture $fixture): ?int
{
    return $fixture->season->standings()->where('team_id', $fixture->home_team_id)->value('points');
}

it('updates the standings when a fixture finishes', function () {
    $fixture = Fixture::factory()->create();

    expect(homePoints($fixture))->toBeNull();

    $fixture->update(['status' => FixtureStatus::Finished, 'score_home' => 2, 'score_away' => 1]);

    expect(homePoints($fixture))->toBe(3);
});

it('updates the standings when a finished score is corrected', function () {
    $fixture = Fixture::factory()->finished(2, 1)->create();

    $fixture->update(['score_home' => 1]);

    expect(homePoints($fixture))->toBe(1);
});

it('updates the standings when a finished fixture is deleted', function () {
    $fixture = Fixture::factory()->finished(2, 1)->create();
    $season = $fixture->season;
    $season->teams()->attach($fixture->home_team_id);

    $fixture->delete();

    expect($season->standings()->where('team_id', $fixture->home_team_id)->value('played'))->toBe(0)
        ->and($season->standings()->where('team_id', $fixture->away_team_id)->exists())->toBeFalse();
});

it('updates both seasons when a finished fixture moves to another season', function () {
    $fixture = Fixture::factory()->finished(2, 1)->create();
    $previousSeason = $fixture->season;
    $newSeason = Season::factory()->create();

    $fixture->update(['season_id' => $newSeason->id]);

    expect($previousSeason->standings()->exists())->toBeFalse()
        ->and($newSeason->standings()->where('team_id', $fixture->home_team_id)->value('points'))->toBe(3);
});
