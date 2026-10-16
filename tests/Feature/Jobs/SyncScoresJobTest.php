<?php

use App\Actions\RefreshLiveScores;
use App\Enums\FixtureStatus;
use App\Jobs\SyncScoresJob;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

uses(RefreshDatabase::class);

beforeEach(function () {
    Sleep::fake();
    Http::preventStrayRequests();
    $this->travelTo(CarbonImmutable::parse('2026-09-17 18:30:00'));

    config([
        'sports-data.provider' => 'api-football',
        'sports-data.api_football.base_url' => 'https://api.test',
        'sports-data.api_football.key' => 'test-key',
        'sports-data.api_football.retries' => 0,
        'sports-data.competitions' => '318:2025',
    ]);
});

function importedFixture(): Fixture
{
    $league = League::factory()->create(['external_source' => 'api-football', 'external_id' => '318']);
    $season = Season::factory()->for($league)->create(['external_id' => '2025']);

    return Fixture::factory()->for($season)->create([
        'home_team_id' => Team::factory()->create(['external_source' => 'api-football', 'external_id' => '102'])->id,
        'away_team_id' => Team::factory()->create(['external_source' => 'api-football', 'external_id' => '103'])->id,
        'external_source' => 'api-football',
        'external_id' => '9003',
    ]);
}

it('updates the status, score and minute of imported fixtures around today', function () {
    $fixture = importedFixture();
    $payload = json_decode(file_get_contents(base_path('tests/Fixtures/api-football/fixtures.json')), true);
    $payload['response'] = [$payload['response'][2]];
    $payload['response'][0]['goals'] = ['home' => 1, 'away' => 0];
    Http::fake(['api.test/fixtures*' => Http::response($payload)]);

    SyncScoresJob::dispatchSync();

    $fixture->refresh();

    expect($fixture->status)->toBe(FixtureStatus::Live)
        ->and($fixture->score_home)->toBe(1)
        ->and($fixture->minute)->toBe(30)
        ->and(Cache::get(RefreshLiveScores::CACHE_KEY)['fixtures'][0]['score_home'])->toBe(1);

    Http::assertSent(fn (Request $request) => $request['from'] === '2026-09-16' && $request['to'] === '2026-09-18');
});

it('skips competitions that have not been imported yet', function () {
    SyncScoresJob::dispatchSync();

    Http::assertNothingSent();
});

it('logs a provider failure without throwing', function () {
    Log::spy();
    importedFixture();
    Http::fake(['api.test/*' => Http::response([], 500)]);

    SyncScoresJob::dispatchSync();

    Log::shouldHaveReceived('warning')->withArgs(fn (string $message) => str_contains($message, 'failed'))->once();
});
