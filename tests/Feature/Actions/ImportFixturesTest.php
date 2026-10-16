<?php

use App\Actions\ImportFixtures;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use App\Services\SportsData\Data\FixtureData;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

function fixtureData(string $id, string $home, string $away, FixtureStatus $status = FixtureStatus::Finished, ?int $homeScore = 2, ?int $awayScore = 1): FixtureData
{
    return new FixtureData($id, $home, $away, CarbonImmutable::parse('2025-08-23 16:00:00'), $status, $homeScore, $awayScore, null, 'Regular Season - 1', 'Demo Stadium');
}

function providerTeam(string $externalId): Team
{
    return Team::factory()->create(['external_source' => 'api-football', 'external_id' => $externalId]);
}

it('creates fixtures for known teams and rebuilds the standings', function () {
    $season = Season::factory()->create();
    $home = providerTeam('101');
    $away = providerTeam('102');

    $imported = app(ImportFixtures::class)->handle('api-football', [fixtureData('9001', '101', '102')], $season);

    $fixture = Fixture::query()->sole();

    expect($imported)->toBe(1)
        ->and($fixture->only(['season_id', 'home_team_id', 'away_team_id', 'score_home', 'score_away', 'external_source', 'external_id']))->toBe([
            'season_id' => $season->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'score_home' => 2,
            'score_away' => 1,
            'external_source' => 'api-football',
            'external_id' => '9001',
        ])
        ->and($fixture->kickoff_at->toDateTimeString())->toBe('2025-08-23 16:00:00')
        ->and($season->standings()->where('team_id', $home->id)->value('points'))->toBe(3);
});

it('updates an existing fixture instead of creating a duplicate', function () {
    $season = Season::factory()->create();
    providerTeam('101');
    providerTeam('102');
    app(ImportFixtures::class)->handle('api-football', [fixtureData('9001', '101', '102', FixtureStatus::Live, 0, 0)], $season);

    app(ImportFixtures::class)->handle('api-football', [fixtureData('9001', '101', '102', FixtureStatus::Finished, 1, 1)], $season);

    $fixture = Fixture::query()->sole();

    expect($fixture->status)->toBe(FixtureStatus::Finished)
        ->and($fixture->score_home)->toBe(1)
        ->and($fixture->score_away)->toBe(1);
});

it('skips and logs fixtures whose teams are unknown', function () {
    Log::spy();
    $season = Season::factory()->create();
    providerTeam('101');

    $imported = app(ImportFixtures::class)->handle('api-football', [fixtureData('9005', '999', '101')], $season);

    expect($imported)->toBe(0)
        ->and(Fixture::query()->count())->toBe(0);

    Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context) => $context['external_id'] === '9005')->once();
});
