<?php

use App\Actions\ListFixtures;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Season;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-09-16 12:00:00'));
});

it('lists a season\'s latest results newest first and its next fixtures soonest first', function () {
    $season = Season::factory()->create();
    $older = Fixture::factory()->for($season)->finished()->create(['kickoff_at' => now()->subDays(7)]);
    $newer = Fixture::factory()->for($season)->finished()->create(['kickoff_at' => now()->subDays(2)]);
    $later = Fixture::factory()->for($season)->create(['kickoff_at' => now()->addDays(5)]);
    $sooner = Fixture::factory()->for($season)->create(['kickoff_at' => now()->addDay()]);
    Fixture::factory()->finished()->create(['kickoff_at' => now()->subDay()]);

    $fixtures = app(ListFixtures::class)->handle($season);

    expect($fixtures['recent']->modelKeys())->toBe([$newer->id, $older->id])
        ->and($fixtures['upcoming']->modelKeys())->toBe([$sooner->id, $later->id]);
});

it('lists a team\'s home and away fixtures only', function () {
    $team = Team::factory()->create();
    $home = Fixture::factory()->finished()->create(['home_team_id' => $team->id, 'kickoff_at' => now()->subDays(2)]);
    $away = Fixture::factory()->finished()->create(['away_team_id' => $team->id, 'kickoff_at' => now()->subDay()]);
    Fixture::factory()->finished()->create(['kickoff_at' => now()->subHours(5)]);

    expect(app(ListFixtures::class)->handle($team)['recent']->modelKeys())->toBe([$away->id, $home->id]);
});

it('limits each list to five fixtures', function () {
    $season = Season::factory()->create();
    Fixture::factory()->for($season)->finished()->count(6)->sequence(fn ($sequence) => ['kickoff_at' => now()->subDays($sequence->index + 1)])->create();
    Fixture::factory()->for($season)->count(6)->sequence(fn ($sequence) => ['kickoff_at' => now()->addDays($sequence->index + 1)])->create();

    $fixtures = app(ListFixtures::class)->handle($season);

    expect($fixtures['recent'])->toHaveCount(5)
        ->and($fixtures['upcoming'])->toHaveCount(5);
});

it('keeps a game that kicked off within three hours among the upcoming fixtures', function (int $minutesAgo, bool $listed) {
    $season = Season::factory()->create();
    Fixture::factory()->for($season)->live()->create(['kickoff_at' => now()->subMinutes($minutesAgo)]);

    expect(app(ListFixtures::class)->handle($season)['upcoming'])->toHaveCount($listed ? 1 : 0);
})->with([
    'kicked off 2h59m ago' => [179, true],
    'kicked off 3h01m ago' => [181, false],
]);

it('keeps the cached lists until a fixture is saved', function () {
    $season = Season::factory()->create();
    $fixture = Fixture::factory()->for($season)->create(['kickoff_at' => now()->addDay()]);
    app(ListFixtures::class)->handle($season);

    Fixture::query()->whereKey($fixture->id)->toBase()->update(['status' => FixtureStatus::Finished->value, 'score_home' => 1, 'score_away' => 0]);
    $cached = app(ListFixtures::class)->handle($season);
    $fixture->refresh()->touch();
    $refreshed = app(ListFixtures::class)->handle($season);

    expect($cached['recent'])->toBeEmpty()
        ->and($refreshed['recent']->modelKeys())->toBe([$fixture->id]);
});
