<?php

use App\Actions\RefreshLiveScores;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 01:30 on 17 September in Cyprus (UTC+3).
    $this->travelTo(CarbonImmutable::parse('2026-09-16 22:30:00'));
});

it('caches live matches first, then today\'s matches in Cyprus time', function () {
    $season = Season::factory()->create();
    $home = Team::factory()->create(['name' => 'Ολυμπιάδα Λευκωσίας']);

    $laterToday = Fixture::factory()->for($season)->create(['kickoff_at' => '2026-09-17 17:00:00', 'home_team_id' => $home->id]);
    $earlierToday = Fixture::factory()->for($season)->finished(2, 1)->create(['kickoff_at' => '2026-09-16 21:30:00']);
    $live = Fixture::factory()->for($season)->live(1, 0, 70)->create(['kickoff_at' => '2026-09-16 19:00:00']);
    Fixture::factory()->for($season)->finished()->create(['kickoff_at' => '2026-09-16 20:00:00']);
    Fixture::factory()->for($season)->create(['kickoff_at' => '2026-09-17 21:30:00']);

    app(RefreshLiveScores::class)->handle();

    $cached = Cache::get(RefreshLiveScores::CACHE_KEY)['fixtures'];

    expect(array_column($cached, 'id'))->toBe([$live->id, $earlierToday->id, $laterToday->id])
        ->and($cached[0]['status'])->toBe(FixtureStatus::Live->value)
        ->and($cached[0]['minute'])->toBe(70)
        ->and($cached[2]['home']['name'])->toBe('Ολυμπιάδα Λευκωσίας')
        ->and($cached[2]['url'])->toBe(route('fixtures.show', $laterToday));
});

it('leaves out matches of inactive leagues', function () {
    $season = Season::factory()->for(League::factory()->inactive())->create();
    Fixture::factory()->for($season)->live()->create();

    app(RefreshLiveScores::class)->handle();

    expect(Cache::get(RefreshLiveScores::CACHE_KEY)['fixtures'])->toBe([]);
});

it('refreshes the cache when a fixture is saved', function () {
    $fixture = Fixture::factory()->live(0, 0)->create();

    $fixture->update(['score_home' => 1]);

    expect(Cache::get(RefreshLiveScores::CACHE_KEY)['fixtures'][0]['score_home'])->toBe(1);
});
